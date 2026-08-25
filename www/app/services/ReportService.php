<?php
/**
 * Report Service - Business Logic Layer
 */

class ReportService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Daily sales report
     */
    public function dailySales($date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT s.id, s.sale_number, s.datetime, s.total, s.payment_method,
                    COUNT(si.id) as item_count
             FROM sales s
             LEFT JOIN sale_items si ON s.id = si.sale_id
             WHERE date(s.datetime) = :date
               AND s.status != 'voided'
               AND s.type = 'sale'
             GROUP BY s.id
             ORDER BY s.datetime DESC"
        );
        $stmt->execute([':date' => $date]);
        $sales = $stmt->fetchAll();

        // Summary — only completed sales (not voided, not returns)
        $summaryStmt = $this->db->prepare(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total,
                    COALESCE(SUM(discount), 0) as discount
             FROM sales
             WHERE date(datetime) = :date
               AND status != 'voided'
               AND type = 'sale'"
        );
        $summaryStmt->execute([':date' => $date]);
        $summary = $summaryStmt->fetch();

        return ['sales' => $sales, 'summary' => $summary, 'date' => $date];
    }

    /**
     * Sales report by date range
     */
    public function salesByRange($dateFrom, $dateTo) {
        $stmt = $this->db->prepare(
            "SELECT date(datetime) as sale_date, COUNT(*) as count, SUM(total) as total
             FROM sales
             WHERE date(datetime) >= :from AND date(datetime) <= :to
               AND status != 'voided'
               AND type = 'sale'
             GROUP BY date(datetime)
             ORDER BY sale_date DESC"
        );
        $stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $daily = $stmt->fetchAll();

        $totalStmt = $this->db->prepare(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total
             FROM sales
             WHERE date(datetime) >= :from AND date(datetime) <= :to
               AND status != 'voided'
               AND type = 'sale'"
        );
        $totalStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $totals = $totalStmt->fetch();

        return ['daily' => $daily, 'totals' => $totals];
    }

    /**
     * Top selling products
     */
    public function topProducts($limit = 20, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT si.product_id, si.product_name,
                       SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_revenue,
                       COUNT(DISTINCT si.sale_id) as sale_count
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                WHERE s.status != 'voided' AND s.type = 'sale'";
        $params = [];

        if ($dateFrom) {
            $sql .= " AND date(s.datetime) >= :from";
            $params[':from'] = $dateFrom;
            if ($dateTo) {
                $sql .= " AND date(s.datetime) <= :to";
                $params[':to'] = $dateTo;
            }
        }

        $sql .= " GROUP BY si.product_id, si.product_name
                   ORDER BY total_qty DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Least selling products
     */
    public function leastProducts($limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.stock_quantity,
                    COALESCE(SUM(si.quantity), 0) as total_sold
             FROM products p
             LEFT JOIN sale_items si ON p.id = si.product_id
             LEFT JOIN sales s ON si.sale_id = s.id AND s.status != 'voided' AND s.type = 'sale'
             WHERE p.is_active = 1
             GROUP BY p.id
             ORDER BY total_sold ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Low stock report
     */
    public function lowStock() {
        $stmt = $this->db->prepare(
            "SELECT * FROM products 
             WHERE is_active = 1 AND stock_quantity <= min_stock AND min_stock > 0
             ORDER BY (stock_quantity / CASE WHEN min_stock > 0 THEN min_stock ELSE 1 END) ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Purchases per supplier
     */
    public function purchasesBySupplier($dateFrom = null, $dateTo = null) {
        $sql = "SELECT s.id, s.name, COUNT(pi.id) as invoice_count, COALESCE(SUM(pi.total), 0) as total
                FROM suppliers s
                LEFT JOIN purchase_invoices pi ON s.id = pi.supplier_id AND pi.status != 'voided' AND pi.type = 'purchase'
                WHERE s.is_active = 1";
        $params = [];

        if ($dateFrom) {
            $sql .= " AND pi.date >= :from";
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND pi.date <= :to";
            $params[':to'] = $dateTo;
        }

        $sql .= " GROUP BY s.id ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Stock movement history
     */
    public function stockMovements($productId = null, $type = '', $dateFrom = null, $dateTo = null, $limit = 100) {
        $sql = "SELECT sm.*, p.name as product_name 
                FROM stock_movements sm 
                LEFT JOIN products p ON sm.product_id = p.id 
                WHERE 1=1";
        $params = [];

        if ($productId) {
            $sql .= " AND sm.product_id = :product_id";
            $params[':product_id'] = $productId;
        }
        if ($type) {
            $sql .= " AND sm.type = :type";
            $params[':type'] = $type;
        }
        if ($dateFrom) {
            $sql .= " AND date(sm.created_at) >= :from";
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND date(sm.created_at) <= :to";
            $params[':to'] = $dateTo;
        }

        $sql .= " ORDER BY sm.created_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Dashboard summary
     */
    public function dashboardSummary() {
        // Today's sales — exclude voided and returns
        $todayStmt = $this->db->prepare(
            "SELECT COUNT(*) as sales_count, COALESCE(SUM(total), 0) as sales_total
             FROM sales
             WHERE date(datetime) = date('now', 'localtime')
               AND status != 'voided'
               AND type = 'sale'"
        );
        $todayStmt->execute();
        $today = $todayStmt->fetch();

        // Product count
        $prodStmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1");
        $prodStmt->execute();
        $productCount = $prodStmt->fetchColumn();

        // Low stock count
        $lowStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity <= min_stock AND min_stock > 0"
        );
        $lowStmt->execute();
        $lowStockCount = $lowStmt->fetchColumn();

        // Monthly sales — exclude voided and returns
        $monthStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) as total FROM sales
             WHERE strftime('%Y-%m', datetime) = strftime('%Y-%m', 'now', 'localtime')
               AND status != 'voided'
               AND type = 'sale'"
        );
        $monthStmt->execute();
        $monthTotal = $monthStmt->fetchColumn();

        return [
            'today_sales_count' => $today['sales_count'],
            'today_sales_total' => $today['sales_total'],
            'product_count' => $productCount,
            'low_stock_count' => $lowStockCount,
            'month_total' => $monthTotal,
        ];
    }

    /**
     * Financial Report (Detailed - Direct from source tables)
     * Reads from actual business tables to avoid distortion from safe_transactions journal entries
     */
    public function financialReport($dateFrom, $dateTo) {

        // === INCOME ===
        // 1. Real Sales (completed, not voided, type=sale only)
        $salesStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) as total, COALESCE(SUM(discount), 0) as discounts
             FROM sales
             WHERE date(datetime) BETWEEN :from AND :to
               AND status != 'voided'
               AND type = 'sale'"
        );
        $salesStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $salesRow = $salesStmt->fetch(PDO::FETCH_ASSOC);
        $totalSales = floatval($salesRow['total']);
        $totalDiscounts = floatval($salesRow['discounts']);

        // 2. Sale Returns (refunds paid back to customers)
        $returnsStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(ABS(total)), 0) as total
             FROM sales
             WHERE date(datetime) BETWEEN :from AND :to
               AND type = 'return'
               AND status != 'voided'"
        );
        $returnsStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $totalReturns = floatval($returnsStmt->fetchColumn());

        // 3. Manual Deposits from safe
        $depositStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM safe_transactions
             WHERE date(date) BETWEEN :from AND :to AND type IN ('deposit', 'opening')"
        );
        $depositStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $totalDeposit = floatval($depositStmt->fetchColumn());

        $netIncome = $totalSales - $totalReturns + $totalDeposit;

        // === EXPENSES ===
        // 4. Real Purchases (completed, not voided)
        $purchaseStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) as total
             FROM purchase_invoices
             WHERE date BETWEEN :from AND :to
               AND status != 'voided'
               AND type = 'purchase'"
        );
        $purchaseStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $totalPurchases = floatval($purchaseStmt->fetchColumn());

        // 5. Salaries & Advances (from safe only - manual entries)
        $salaryStmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN type IN ('salary','salary_payment') THEN amount ELSE 0 END), 0) as salaries,
                COALESCE(SUM(CASE WHEN type = 'advance' THEN amount ELSE 0 END), 0) as advances
             FROM safe_transactions
             WHERE date(date) BETWEEN :from AND :to"
        );
        $salaryStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $salaryRow = $salaryStmt->fetch(PDO::FETCH_ASSOC);
        $totalSalaries = floatval($salaryRow['salaries']);
        $totalAdvances = floatval($salaryRow['advances']);

        // 6. Operational Expenses
        $expenseStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM safe_transactions
             WHERE date(date) BETWEEN :from AND :to AND type = 'expense'"
        );
        $expenseStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $totalExpenses = floatval($expenseStmt->fetchColumn());

        // 7. Personal Withdrawals (owner draws — NOT an operating expense)
        $withdrawalStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM safe_transactions
             WHERE date(date) BETWEEN :from AND :to AND type = 'withdrawal'"
        );
        $withdrawalStmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
        $totalWithdrawals = floatval($withdrawalStmt->fetchColumn());

        // Operating expenses ONLY (excludes withdrawals which are equity, not costs)
        $totalOperatingExpenses = $totalPurchases + $totalSalaries + $totalAdvances + $totalExpenses;
        $netProfit = $netIncome - $totalOperatingExpenses;

        // === DAILY BREAKDOWN ===
        $dailyStmt = $this->db->prepare(
            "SELECT
                report_date,
                SUM(sales_total) as sales,
                SUM(returns_total) as returns,
                SUM(deposit_total) as deposits,
                SUM(purchase_total) as purchases,
                SUM(salary_total) as salaries,
                SUM(advance_total) as advances,
                SUM(expense_total) as expenses,
                SUM(withdrawal_total) as withdrawals
             FROM (
                SELECT date(datetime) as report_date,
                    SUM(total) as sales_total, 0 as returns_total, 0 as deposit_total,
                    0 as purchase_total, 0 as salary_total, 0 as advance_total,
                    0 as expense_total, 0 as withdrawal_total
                FROM sales
                WHERE date(datetime) BETWEEN :from AND :to AND status != 'voided' AND type = 'sale'
                GROUP BY date(datetime)

                UNION ALL

                SELECT date(datetime) as report_date,
                    0, SUM(ABS(total)) as returns_total, 0, 0, 0, 0, 0, 0
                FROM sales
                WHERE date(datetime) BETWEEN :from2 AND :to2 AND type = 'return' AND status != 'voided'
                GROUP BY date(datetime)

                UNION ALL

                SELECT date(date) as report_date,
                    0, 0,
                    SUM(CASE WHEN type IN ('deposit','opening') THEN amount ELSE 0 END),
                    SUM(CASE WHEN type = 'purchase' THEN amount ELSE 0 END),
                    SUM(CASE WHEN type IN ('salary','salary_payment') THEN amount ELSE 0 END),
                    SUM(CASE WHEN type = 'advance' THEN amount ELSE 0 END),
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END),
                    SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END)
                FROM safe_transactions
                WHERE date(date) BETWEEN :from3 AND :to3
                GROUP BY date(date)
             )
             GROUP BY report_date
             ORDER BY report_date DESC"
        );
        $dailyStmt->execute([
            ':from'  => $dateFrom, ':to'  => $dateTo,
            ':from2' => $dateFrom, ':to2' => $dateTo,
            ':from3' => $dateFrom, ':to3' => $dateTo,
        ]);
        $dailyRows = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

        $daily = [];
        foreach ($dailyRows as $row) {
            $daySales     = floatval($row['sales']);
            $dayReturns   = floatval($row['returns']);
            $dayDeposits  = floatval($row['deposits']);
            $dayPurchases = floatval($row['purchases']);
            $daySalaries  = floatval($row['salaries']);
            $dayAdvances  = floatval($row['advances']);
            $dayExpenses  = floatval($row['expenses']);
            $dayWithdrawals = floatval($row['withdrawals']);

            $dayIncome  = $daySales - $dayReturns + $dayDeposits;
            $dayOpEx    = $dayPurchases + $daySalaries + $dayAdvances + $dayExpenses;
            $dayProfit  = $dayIncome - $dayOpEx;

            $daily[] = [
                'date'        => $row['report_date'],
                'sales'       => $daySales,
                'returns'     => $dayReturns,
                'deposits'    => $dayDeposits,
                'income'      => $dayIncome,
                'purchases'   => $dayPurchases,
                'salaries'    => $daySalaries,
                'advances'    => $dayAdvances,
                'expenses'    => $dayExpenses,
                'withdrawals' => $dayWithdrawals,
                'op_expenses' => $dayOpEx,
                'profit'      => $dayProfit,
            ];
        }

        return [
            'daily'  => $daily,
            'totals' => [
                'sales'       => $totalSales,
                'returns'     => $totalReturns,
                'discounts'   => $totalDiscounts,
                'deposits'    => $totalDeposit,
                'income'      => $netIncome,
                'purchases'   => $totalPurchases,
                'salaries'    => $totalSalaries,
                'advances'    => $totalAdvances,
                'expenses'    => $totalExpenses,
                'withdrawals' => $totalWithdrawals,
                'op_expenses' => $totalOperatingExpenses,
                'profit'      => $netProfit,
            ]
        ];
    }

    /**
     * Sales by category breakdown
     */
    public function salesByCategory($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    COALESCE(p.category, 'بدون قسم') as category,
                    COUNT(DISTINCT si.sale_id) as sale_count,
                    SUM(si.quantity) as total_qty,
                    SUM(si.subtotal) as total_revenue
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                LEFT JOIN products p ON si.product_id = p.id
                WHERE s.status != 'voided' AND s.type = 'sale'";
        $params = [];
        if ($dateFrom) {
            $sql .= " AND date(s.datetime) >= :from";
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND date(s.datetime) <= :to";
            $params[':to'] = $dateTo;
        }
        $sql .= " GROUP BY COALESCE(p.category, 'بدون قسم') ORDER BY total_revenue DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Profit margin per product
     */
    public function productProfitMargin($dateFrom = null, $dateTo = null, $limit = 30) {
        $sql = "SELECT 
                    si.product_id,
                    si.product_name,
                    SUM(si.quantity) as total_qty,
                    SUM(si.subtotal) as total_revenue,
                    COALESCE(p.purchase_price, 0) as avg_cost,
                    SUM(si.quantity) * COALESCE(p.purchase_price, 0) as total_cost,
                    SUM(si.subtotal) - (SUM(si.quantity) * COALESCE(p.purchase_price, 0)) as profit,
                    CASE WHEN SUM(si.subtotal) > 0 
                        THEN ((SUM(si.subtotal) - (SUM(si.quantity) * COALESCE(p.purchase_price, 0))) / SUM(si.subtotal)) * 100 
                        ELSE 0 END as margin_pct
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                LEFT JOIN products p ON si.product_id = p.id
                WHERE s.status != 'voided' AND s.type = 'sale'";
        $params = [];
        if ($dateFrom) {
            $sql .= " AND date(s.datetime) >= :from";
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND date(s.datetime) <= :to";
            $params[':to'] = $dateTo;
        }
        $sql .= " GROUP BY si.product_id, si.product_name ORDER BY profit DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Dead stock report (no sales in last N days)
     */
    public function deadStock($days = 30) {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.barcode, p.stock_quantity, p.purchase_price, p.sale_price_unit,
                    p.category, p.type,
                    COALESCE(MAX(s.datetime), 'لم يُبَع') as last_sale_date,
                    COALESCE(SUM(si.quantity), 0) as total_sold,
                    (p.stock_quantity * p.purchase_price) as stock_value
             FROM products p
             LEFT JOIN sale_items si ON p.id = si.product_id
             LEFT JOIN sales s ON si.sale_id = s.id AND s.status != 'voided' AND s.type = 'sale'
             WHERE p.is_active = 1 AND p.stock_quantity > 0
             GROUP BY p.id
             HAVING last_sale_date = 'لم يُبَع' OR MAX(s.datetime) < date('now', '-' || ? || ' days')
             ORDER BY stock_value DESC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Cashier performance report
     */
    public function cashierPerformance($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    u.id as user_id,
                    u.name as cashier_name,
                    COUNT(s.id) as sale_count,
                    COALESCE(SUM(s.total), 0) as total_sales,
                    COALESCE(SUM(s.discount), 0) as total_discounts,
                    COALESCE(AVG(s.total), 0) as avg_sale
                FROM users u
                LEFT JOIN sales s ON s.shift_id IN (SELECT id FROM shifts WHERE user_id = u.id)
                    AND s.status != 'voided' AND s.type = 'sale'";
        $params = [];
        if ($dateFrom) {
            $sql .= " AND date(s.datetime) >= :from";
            $params[':from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND date(s.datetime) <= :to";
            $params[':to'] = $dateTo;
        }
        $sql .= " GROUP BY u.id, u.name HAVING sale_count > 0 ORDER BY total_sales DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Customer debtors report
     */
    public function customerDebtors() {
        $stmt = $this->db->prepare(
            "SELECT id, name, phone, balance, credit_limit, created_at
             FROM customers WHERE balance > 0 AND is_active = 1
             ORDER BY balance DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
        