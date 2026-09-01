<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestTenantSeeder extends Seeder
{
    public function run(): void
    {
        $this->createTenantWithOwner(
            name: 'Tenant 01',
            slug: 'tenant01',
            userEmail: 'tenant01@test.com',
            password: 'tenant01',
            prefix: 'T01',
        );

        $this->createTenantWithOwner(
            name: 'Tenant 02',
            slug: 'tenant02',
            userEmail: 'tenant02@test.com',
            password: 'tenant02',
            prefix: 'T02',
        );
    }

    private function createTenantWithOwner(
        string $name,
        string $slug,
        string $userEmail,
        string $password,
        string $prefix,
    ): void {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'business_name' => $name,
                'domain' => $slug.'.'.parse_url(config('app.url'), PHP_URL_HOST),
                'is_active' => true,
            ]
        );

        $tenant->execute(function () use ($prefix) {
            $category = Category::query()->firstOrCreate(['name' => "{$prefix} - Kategori"]);

            $product = Product::query()->firstOrCreate(
                ['name' => "{$prefix} - Produk"],
                [
                    'description' => "Produk contoh milik {$prefix}",
                    'category_id' => $category->id,
                    'image' => '',
                ]
            );

            if ($product->variants()->count() === 0) {
                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'name' => 'Reguler',
                    'price' => 15000,
                ]);
            }

            $addonCategory = AddonCategory::query()->firstOrCreate(['name' => "{$prefix} - Opsi"]);
            $addon = Addon::query()->firstOrCreate(
                ['name' => "{$prefix} - Tambahan"],
                ['addon_category_id' => $addonCategory->id, 'price' => 3000],
            );

            if (! $product->addons()->where('addons.id', $addon->id)->exists()) {
                $product->addons()->attach($addon->id);
            }
        });

        // withoutGlobalScopes(): outside the $tenant->execute() block above,
        // there is no "current tenant" set, so TenantScope would otherwise
        // filter this to tenant_id IS NULL and never find the row we want.
        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $userEmail)
            ->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $name.' Owner',
                'email' => $userEmail,
                'password' => Hash::make($password),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'is_active' => true,
            ]);
        }

        if (! $user->hasRole('owner')) {
            $user->assignRole('owner');
        }

        $this->command?->info("Tenant '{$slug}' ready. Login: {$userEmail} / {$password}");
    }
}
