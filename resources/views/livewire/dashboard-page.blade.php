<div class="grid grid-cols-12 gap-4 overflow-x-hidden md:gap-6">
    <div class="col-span-12 min-w-0 space-y-6 xl:col-span-7">
        <x-ecommerce.ecommerce-metrics
            :transactions="$transactionsCount"
            :transactions-delta-percent="$transactionsDeltaPercent"
            :transactions-delta-up="$transactionsDeltaUp"
            :revenue-amount="$todayRevenueAmount"
        />
        <x-ecommerce.statistics-chart :series="$statisticsSeries" :categories="$statisticsCategories" :from="$statisticsFrom" :to="$statisticsTo" />
        <x-ecommerce.latest-transactions :transactions="$latestTransactions" />
    </div>

    <div class="col-span-12 min-w-0 space-y-6 xl:col-span-5">
        <x-ecommerce.monthly-target
            :progress-percent="$monthlyTargetProgressPercent"
            :delta-percent="$monthlyTargetDeltaPercent"
            :delta-up="$monthlyTargetDeltaUp"
            :target-amount="$monthlyTargetAmount"
            :revenue-amount="$monthlyRevenueAmount"
            :today-amount="$todayRevenueAmount"
        />
        <x-ecommerce.today-product-sales :products="$todayProductSales" :total-revenue="$productSalesRevenue" :selected-date="$productSalesDate" />
        <x-ecommerce.best-selling-products :products="$bestSellingProducts" />
    </div>
</div>
