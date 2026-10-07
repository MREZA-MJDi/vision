<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class DoubleEntryAccountingService
{
    public const ACCOUNTS = [
        'cash' => ['name' => 'صندوق', 'type' => 'asset'],
        'bank' => ['name' => 'بانک', 'type' => 'asset'],
        'cheques_receivable' => ['name' => 'اسناد دریافتنی / چک', 'type' => 'asset'],
        'accounts_receivable' => ['name' => 'حساب‌های دریافتنی', 'type' => 'asset'],
        'inventory' => ['name' => 'موجودی کالا', 'type' => 'asset'],
        'sales' => ['name' => 'فروش', 'type' => 'revenue'],
        'sales_returns' => ['name' => 'برگشت از فروش', 'type' => 'revenue'],
        'expenses' => ['name' => 'هزینه‌ها', 'type' => 'expense'],
    ];

    public function recordSale(
        Model $reference,
        float $amount,
        string $settlementAccount,
        string $sourceKey,
        string $description
    ): JournalEntry {
        return $this->post(
            reference: $reference,
            amount: $amount,
            sourceKey: $sourceKey,
            debitAccount: $settlementAccount,
            creditAccount: 'sales',
            description: $description,
        );
    }

    public function recordRefund(
        Model $reference,
        float $amount,
        string $settlementAccount,
        string $sourceKey,
        string $description
    ): JournalEntry {
        return $this->post(
            reference: $reference,
            amount: $amount,
            sourceKey: $sourceKey,
            debitAccount: 'sales_returns',
            creditAccount: $settlementAccount,
            description: $description,
        );
    }

    private function post(
        Model $reference,
        float $amount,
        string $sourceKey,
        string $debitAccount,
        string $creditAccount,
        string $description
    ): JournalEntry {
        abort_if($amount <= 0, 422, 'مبلغ سند حسابداری باید بیشتر از صفر باشد.');

        return DB::transaction(function () use (
            $reference,
            $amount,
            $sourceKey,
            $debitAccount,
            $creditAccount,
            $description
        ): JournalEntry {
            $existing = JournalEntry::query()
                ->with('lines')
                ->where('source_key', $sourceKey)
                ->first();

            if ($existing) {
                return $existing;
            }

            $debit = $this->account($debitAccount);
            $credit = $this->account($creditAccount);

            $entry = JournalEntry::create([
                'entry_number' => 'JE-' . now()->format('YmdHisv') . '-' . random_int(100, 999),
                'source_key' => $sourceKey,
                'entry_date' => now()->toDateString(),
                'description' => $description,
                'reference_type' => $reference::class,
                'reference_id' => $reference->getKey(),
            ]);

            $entry->lines()->createMany([
                [
                    'ledger_account_id' => $debit->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => $description,
                ],
                [
                    'ledger_account_id' => $credit->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => $description,
                ],
            ]);

            return $entry->load('lines.account');
        });
    }

    private function account(string $code): LedgerAccount
    {
        abort_unless(isset(self::ACCOUNTS[$code]), 422, 'حساب حسابداری نامعتبر است.');

        return LedgerAccount::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => self::ACCOUNTS[$code]['name'],
                'type' => self::ACCOUNTS[$code]['type'],
                'is_active' => true,
            ]
        );
    }
}
