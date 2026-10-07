<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFinancialTransactionRequest;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AdminFinancialController extends Controller
{
    public function index(Request $request): View
    {
        $query = FinancialTransaction::query()
            ->with('createdBy')
            ->when(
                $request->filled('q'),
                function ($query) use ($request): void {
                    $search = $request->string('q')->toString();

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where(
                                'category',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'description',
                                'like',
                                '%' . $search . '%'
                            );
                    });
                }
            )
            ->when(
                $request->filled('type'),
                fn ($query) => $query->where(
                    'type',
                    $request->input('type')
                )
            )
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $request->input('from')
                )
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $request->input('to')
                )
            );

        $transactions = $query
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summaryQuery = FinancialTransaction::query()
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $request->input('from')
                )
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $request->input('to')
                )
            );

        $income = (float) (clone $summaryQuery)
            ->income()
            ->sum('amount');

        $expense = (float) (clone $summaryQuery)
            ->expense()
            ->sum('amount');

        $ledgerSummary = JournalLine::query()
            ->selectRaw('ledger_accounts.code, ledger_accounts.name, SUM(journal_lines.debit) as debit, SUM(journal_lines.credit) as credit')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate('journal_entries.entry_date', '>=', $request->input('from'))
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate('journal_entries.entry_date', '<=', $request->input('to'))
            )
            ->groupBy('ledger_accounts.id', 'ledger_accounts.code', 'ledger_accounts.name')
            ->orderBy('ledger_accounts.code')
            ->get();

        $journalEntries = JournalEntry::query()
            ->with('lines.account')
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate('entry_date', '>=', $request->input('from'))
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate('entry_date', '<=', $request->input('to'))
            )
            ->latest('entry_date')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.accounting.index', [
            'transactions' => $transactions,
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'ledgerSummary' => $ledgerSummary,
            'journalEntries' => $journalEntries,
        ]);
    }

    public function show(
        FinancialTransaction $transaction
    ): View {
        $transaction->load('createdBy');

        return view(
            'admin.accounting.show',
            compact('transaction')
        );
    }

    public function store(
        StoreFinancialTransactionRequest $request
    ): RedirectResponse {
        try {
            DB::transaction(function () use ($request): void {
                FinancialTransaction::create(
                    $request->validated() + [
                        'created_by' => auth()->id(),
                    ]
                );
            });

            return back()->with(
                'success',
                'تراکنش مالی با موفقیت ثبت شد.'
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'ثبت تراکنش مالی انجام نشد.'
                );
        }
    }
}
