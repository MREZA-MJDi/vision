<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->morphs('mediable');

            $table->string('collection', 80)
                ->default('default')
                ->index();

            $table->string('disk', 40)
                ->default('public');

            $table->string('path', 500);

            $table->string('original_name', 255)
                ->nullable();

            $table->string('mime_type', 100)
                ->nullable();

            $table->unsignedBigInteger('size')
                ->nullable();

            $table->unsignedInteger('width')
                ->nullable();

            $table->unsignedInteger('height')
                ->nullable();

            $table->string('alt_text', 255)
                ->nullable();

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->json('metadata')
                ->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['disk', 'path'],
                'media_disk_path_unique'
            );

            $table->index(
                [
                    'mediable_type',
                    'mediable_id',
                    'collection',
                    'sort_order',
                ],
                'media_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
