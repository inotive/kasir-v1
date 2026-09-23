<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Imports a filtered, ID-remapped copy of tenant "viko"'s data
 * (from a production-ish backup) into a local "viko" tenant for
 * feature testing. No user/staff accounts are created or copied.
 *
 * Usage: php artisan db:seed --class=VikoSampleDataSeeder
 * Safe to re-run: existing data for the "viko" tenant is wiped
 * and reseeded from database/seeders/fixtures/viko_sample.json.
 */
class VikoSampleDataSeeder extends Seeder
{
    private Tenant $tenant;

    /** @var array<string, array<int|string, int>> */
    private array $map = [];

    private array $fixture;

    private const WIPE_ORDER = [
        'inventory_movements', 'transaction_events', 'transaction_item_addons',
        'transaction_items', 'transactions', 'monthly_revenue_targets',
        'operating_expenses', 'stock_opname_items', 'stock_opnames',
        'purchase_items', 'purchases', 'voucher_campaign_category',
        'voucher_codes', 'voucher_campaigns', 'settings', 'members',
        'product_addons', 'product_variant_recipes', 'product_package_items',
        'product_variants', 'products', 'ingredients', 'suppliers',
        'dining_tables', 'printer_sources', 'addons', 'addon_categories',
        'categories',
    ];

    public function run(): void
    {
        $this->fixture = json_decode(
            file_get_contents(database_path('seeders/fixtures/viko_sample.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () {
            $this->tenant = Tenant::query()->updateOrCreate(
                ['slug' => 'viko'],
                [
                    'name' => 'viko',
                    'business_name' => 'Jual Kucing',
                    'domain' => 'viko.'.config('app.tenant_domain'),
                    'is_active' => true,
                ]
            );

            $this->wipeExisting();

            $this->seedCategories();
            $this->seedAddonCategories();
            $this->seedAddons();
            $this->seedPrinterSources();
            $this->seedDiningTables();
            $this->seedSuppliers();
            $this->seedIngredients();
            $this->seedProducts();
            $this->seedProductVariants();
            $this->seedProductPackageItems();
            $this->seedProductVariantRecipes();
            $this->seedProductAddons();
            $this->seedMembers();
            $this->seedSettings();
            $this->seedVoucherCampaigns();
            $this->seedVoucherCodes();
            $this->seedVoucherCampaignCategory();
            $this->seedPurchases();
            $this->seedPurchaseItems();
            $this->seedStockOpnames();
            $this->seedStockOpnameItems();
            $this->seedOperatingExpenses();
            $this->seedMonthlyRevenueTargets();
            $this->seedTransactions();
            $this->seedTransactionItems();
            $this->seedTransactionItemAddons();
            $this->seedTransactionEvents();
            $this->seedInventoryMovements();
        });

        $this->command?->info("Viko sample data seeded for tenant #{$this->tenant->id} (slug: viko).");
    }

    private function wipeExisting(): void
    {
        foreach (self::WIPE_ORDER as $table) {
            DB::table($table)->where('tenant_id', $this->tenant->id)->delete();
        }
    }

    private function rows(string $table): array
    {
        return $this->fixture[$table] ?? [];
    }

    private function mapped(string $table, int|string|null $oldId): ?int
    {
        if ($oldId === null) {
            return null;
        }

        return $this->map[$table][$oldId] ?? null;
    }

    private function insertMapped(string $table, callable $transform): void
    {
        foreach ($this->rows($table) as $row) {
            $oldId = $row['id'];
            unset($row['id']);

            $data = $transform($row);
            $data['tenant_id'] = $this->tenant->id;

            $this->map[$table][$oldId] = DB::table($table)->insertGetId($data);
        }
    }

    private function seedCategories(): void
    {
        $this->insertMapped('categories', fn (array $row) => $row);
    }

    private function seedAddonCategories(): void
    {
        $this->insertMapped('addon_categories', fn (array $row) => $row);
    }

    private function seedAddons(): void
    {
        $this->insertMapped('addons', function (array $row) {
            $row['addon_category_id'] = $this->mapped('addon_categories', $row['addon_category_id']);

            return $row;
        });
    }

    private function seedPrinterSources(): void
    {
        $this->insertMapped('printer_sources', fn (array $row) => $row);
    }

    private function seedDiningTables(): void
    {
        $this->insertMapped('dining_tables', fn (array $row) => $row);
    }

    private function seedSuppliers(): void
    {
        $this->insertMapped('suppliers', fn (array $row) => $row);
    }

    private function seedIngredients(): void
    {
        $this->insertMapped('ingredients', fn (array $row) => $row);
    }

    private function seedProducts(): void
    {
        $this->insertMapped('products', function (array $row) {
            $row['category_id'] = $this->mapped('categories', $row['category_id']);
            $row['printer_source_id'] = $this->mapped('printer_sources', $row['printer_source_id']);

            return $row;
        });
    }

    private function seedProductVariants(): void
    {
        $this->insertMapped('product_variants', function (array $row) {
            $row['product_id'] = $this->mapped('products', $row['product_id']);

            return $row;
        });
    }

    private function seedProductPackageItems(): void
    {
        $this->insertMapped('product_package_items', function (array $row) {
            $row['package_product_id'] = $this->mapped('products', $row['package_product_id']);
            $row['component_product_variant_id'] = $this->mapped('product_variants', $row['component_product_variant_id']);

            return $row;
        });
    }

    private function seedProductVariantRecipes(): void
    {
        $this->insertMapped('product_variant_recipes', function (array $row) {
            $row['product_variant_id'] = $this->mapped('product_variants', $row['product_variant_id']);
            $row['ingredient_id'] = $this->mapped('ingredients', $row['ingredient_id']);

            return $row;
        });
    }

    private function seedProductAddons(): void
    {
        $this->insertMapped('product_addons', function (array $row) {
            $row['product_id'] = $this->mapped('products', $row['product_id']);
            $row['addon_id'] = $this->mapped('addons', $row['addon_id']);

            return $row;
        });
    }

    private function seedMembers(): void
    {
        $this->insertMapped('members', fn (array $row) => $row);
    }

    private function seedSettings(): void
    {
        $this->insertMapped('settings', fn (array $row) => $row);
    }

    private function seedVoucherCampaigns(): void
    {
        $this->insertMapped('voucher_campaigns', function (array $row) {
            $row['created_by_user_id'] = null;

            return $row;
        });
    }

    private function seedVoucherCodes(): void
    {
        $this->insertMapped('voucher_codes', function (array $row) {
            $row['voucher_campaign_id'] = $this->mapped('voucher_campaigns', $row['voucher_campaign_id']);

            return $row;
        });
    }

    private function seedVoucherCampaignCategory(): void
    {
        $this->insertMapped('voucher_campaign_category', function (array $row) {
            $row['voucher_campaign_id'] = $this->mapped('voucher_campaigns', $row['voucher_campaign_id']);
            $row['category_id'] = $this->mapped('categories', $row['category_id']);

            return $row;
        });
    }

    private function seedPurchases(): void
    {
        $this->insertMapped('purchases', function (array $row) {
            $row['supplier_id'] = $this->mapped('suppliers', $row['supplier_id']);

            return $row;
        });
    }

    private function seedPurchaseItems(): void
    {
        $this->insertMapped('purchase_items', function (array $row) {
            $row['purchase_id'] = $this->mapped('purchases', $row['purchase_id']);
            $row['ingredient_id'] = $this->mapped('ingredients', $row['ingredient_id']);

            return $row;
        });
    }

    private function seedStockOpnames(): void
    {
        $this->insertMapped('stock_opnames', fn (array $row) => $row);
    }

    private function seedStockOpnameItems(): void
    {
        $this->insertMapped('stock_opname_items', function (array $row) {
            $row['stock_opname_id'] = $this->mapped('stock_opnames', $row['stock_opname_id']);
            $row['ingredient_id'] = $this->mapped('ingredients', $row['ingredient_id']);

            return $row;
        });
    }

    private function seedOperatingExpenses(): void
    {
        $this->insertMapped('operating_expenses', function (array $row) {
            $row['created_by_user_id'] = null;

            return $row;
        });
    }

    private function seedMonthlyRevenueTargets(): void
    {
        $this->insertMapped('monthly_revenue_targets', fn (array $row) => $row);
    }

    private function seedTransactions(): void
    {
        $this->insertMapped('transactions', function (array $row) {
            $row['member_id'] = $this->mapped('members', $row['member_id']);
            $row['dining_table_id'] = $this->mapped('dining_tables', $row['dining_table_id']);
            $row['voucher_campaign_id'] = $this->mapped('voucher_campaigns', $row['voucher_campaign_id']);
            $row['voucher_code_id'] = $this->mapped('voucher_codes', $row['voucher_code_id']);
            $row['manual_discount_by_user_id'] = null;
            $row['voided_by_user_id'] = null;
            $row['refunded_by_user_id'] = null;
            $row['kitchen_processed_by_user_id'] = null;

            return $row;
        });
    }

    private function seedTransactionItems(): void
    {
        $originalParents = [];

        foreach ($this->rows('transaction_items') as $row) {
            $oldId = $row['id'];
            $originalParents[$oldId] = $row['parent_transaction_item_id'];

            unset($row['id']);
            $row['parent_transaction_item_id'] = null;
            $row['transaction_id'] = $this->mapped('transactions', $row['transaction_id']);
            $row['product_id'] = $this->mapped('products', $row['product_id']);
            $row['product_variant_id'] = $this->mapped('product_variants', $row['product_variant_id']);
            $row['tenant_id'] = $this->tenant->id;

            $this->map['transaction_items'][$oldId] = DB::table('transaction_items')->insertGetId($row);
        }

        foreach ($originalParents as $oldId => $oldParentId) {
            if ($oldParentId === null) {
                continue;
            }

            DB::table('transaction_items')
                ->where('id', $this->map['transaction_items'][$oldId])
                ->update(['parent_transaction_item_id' => $this->mapped('transaction_items', $oldParentId)]);
        }
    }

    private function seedTransactionItemAddons(): void
    {
        $this->insertMapped('transaction_item_addons', function (array $row) {
            $row['transaction_item_id'] = $this->mapped('transaction_items', $row['transaction_item_id']);
            $row['addon_id'] = $this->mapped('addons', $row['addon_id']);

            return $row;
        });
    }

    private function seedTransactionEvents(): void
    {
        $this->insertMapped('transaction_events', function (array $row) {
            $row['transaction_id'] = $this->mapped('transactions', $row['transaction_id']);
            $row['actor_user_id'] = null;

            return $row;
        });
    }

    private function seedInventoryMovements(): void
    {
        $this->insertMapped('inventory_movements', function (array $row) {
            $row['ingredient_id'] = $this->mapped('ingredients', $row['ingredient_id']);
            $row['supplier_id'] = $this->mapped('suppliers', $row['supplier_id']);

            if ($row['reference_id'] !== null && $row['reference_type'] !== null) {
                $row['reference_id'] = $this->mapped($row['reference_type'], $row['reference_id']);
            }

            return $row;
        });
    }
}
