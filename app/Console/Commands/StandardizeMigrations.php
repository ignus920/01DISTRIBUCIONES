<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StandardizeMigrations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migrate:standardize {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Standardize migration files to use Laravel conventions for timestamps and field naming';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🔍 Scanning migration files for standardization...');

        $migrationsPath = database_path('migrations');
        $files = File::glob($migrationsPath . '/*.php');

        $changes = [];

        foreach ($files as $file) {
            $content = File::get($file);
            $originalContent = $content;

            // Standardize timestamps
            $content = $this->standardizeTimestamps($content);

            // Standardize field naming
            $content = $this->standardizeFieldNaming($content);

            // Standardize primary key
            $content = $this->standardizePrimaryKey($content);

            if ($content !== $originalContent) {
                $fileName = basename($file);
                $changes[] = $fileName;

                if (!$isDryRun) {
                    File::put($file, $content);
                    $this->line("✅ Updated: $fileName");
                } else {
                    $this->line("📝 Would update: $fileName");
                }
            }
        }

        if (empty($changes)) {
            $this->info('✨ All migrations are already standardized!');
        } else {
            $this->info("\n📊 Summary:");
            $this->info("Files that " . ($isDryRun ? 'would be' : 'were') . " updated: " . count($changes));

            if ($isDryRun) {
                $this->warn("\n🧪 This was a dry run. Use --dry-run=false to make actual changes.");
                $this->info("💡 To apply changes: php artisan migrate:standardize");
            } else {
                $this->info("\n✅ Migrations have been standardized!");
                $this->warn("⚠️  Remember to:");
                $this->warn("   1. Update your models to use standard timestamps");
                $this->warn("   2. Run migrations on a test database first");
                $this->warn("   3. Update any code that references old field names");
            }
        }

        return 0;
    }

    /**
     * Standardize timestamp fields
     */
    private function standardizeTimestamps(string $content): string
    {
        // Replace custom timestamp fields with Laravel standards
        $patterns = [
            // Replace manual timestamp definitions with $table->timestamps()
            '/\$table->dateTime\([\'"]createdAt[\'"]\)[^;]*;\s*\$table->dateTime\([\'"]updatedAt[\'"]\)[^;]*;/m' => '$table->timestamps();',
            '/\$table->dateTime\([\'"]created_at[\'"]\)[^;]*;\s*\$table->dateTime\([\'"]updated_at[\'"]\)[^;]*;/m' => '$table->timestamps();',

            // Replace deleted_at with softDeletes()
            '/\$table->dateTime\([\'"]deletedAt[\'"]\)[^;]*;/m' => '$table->softDeletes();',
            '/\$table->dateTime\([\'"]deleted_at[\'"]\)->nullable\(\);/m' => '$table->softDeletes();',

            // Individual timestamp replacements if not grouped
            '/\$table->dateTime\([\'"]createdAt[\'"]\)([^;]*);/' => '$table->timestamp(\'created_at\')$1;',
            '/\$table->dateTime\([\'"]updatedAt[\'"]\)([^;]*);/' => '$table->timestamp(\'updated_at\')$1;',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * Standardize field naming conventions
     */
    private function standardizeFieldNaming(string $content): string
    {
        // Common camelCase to snake_case conversions
        $fieldMappings = [
            'businessName' => 'business_name',
            'billingEmail' => 'billing_email',
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'secondName' => 'second_name',
            'secondLastName' => 'second_last_name',
            'integrationDataId' => 'integration_data_id',
            'checkDigit' => 'check_digit',
            'typePerson' => 'type_person',
            'typeIdentificationId' => 'type_identification_id',
            'regimeId' => 'regime_id',
            'fiscalResponsabilityId' => 'fiscal_responsibility_id',
            'companyId' => 'company_id',
            'cityId' => 'city_id',
            'termId' => 'term_id',
            'billingFormat' => 'billing_format',
            'creditLimit' => 'credit_limit',
            'priceList' => 'price_list',
            'merchantId' => 'merchant_id',
            'modulId' => 'module_id',
            'plainId' => 'plain_id',
            'merchantTypeId' => 'merchant_type_id',
            'contactId' => 'contact_id',
            'warehouseId' => 'warehouse_id',
        ];

        foreach ($fieldMappings as $camelCase => $snakeCase) {
            // Replace in string literals (field names in migrations)
            $content = preg_replace(
                "/(['\"])" . preg_quote($camelCase) . "(['\"])/",
                "$1$snakeCase$2",
                $content
            );
        }

        return $content;
    }

    /**
     * Standardize primary key definition
     */
    private function standardizePrimaryKey(string $content): string
    {
        // Replace $table->integer('id', true) with $table->id()
        $content = preg_replace(
            '/\$table->integer\([\'"]id[\'"]\s*,\s*true\);/',
            '$table->id();',
            $content
        );

        return $content;
    }
}