<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountTypesSeeder extends Seeder
{
    public function run(): void
    {
        $accountTypes = [
            // Assets
            ['name' => 'Bank', 'code' => 'BANK', 'classification' => 'asset', 'normal_balance' => 'debit', 'display_order' => 1, 'is_system' => true],
            ['name' => 'Accounts Receivable', 'code' => 'AR', 'classification' => 'asset', 'normal_balance' => 'debit', 'display_order' => 2, 'is_system' => true],
            ['name' => 'Other Current Asset', 'code' => 'OCA', 'classification' => 'asset', 'normal_balance' => 'debit', 'display_order' => 3, 'is_system' => true],
            ['name' => 'Fixed Asset', 'code' => 'FA', 'classification' => 'asset', 'normal_balance' => 'debit', 'display_order' => 4, 'is_system' => true],
            ['name' => 'Other Asset', 'code' => 'OA', 'classification' => 'asset', 'normal_balance' => 'debit', 'display_order' => 5, 'is_system' => true],

            // Liabilities
            ['name' => 'Accounts Payable', 'code' => 'AP', 'classification' => 'liability', 'normal_balance' => 'credit', 'display_order' => 10, 'is_system' => true],
            ['name' => 'Credit Card', 'code' => 'CC', 'classification' => 'liability', 'normal_balance' => 'credit', 'display_order' => 11, 'is_system' => true],
            ['name' => 'Other Current Liability', 'code' => 'OCL', 'classification' => 'liability', 'normal_balance' => 'credit', 'display_order' => 12, 'is_system' => true],
            ['name' => 'Long Term Liability', 'code' => 'LTL', 'classification' => 'liability', 'normal_balance' => 'credit', 'display_order' => 13, 'is_system' => true],

            // Equity
            ['name' => 'Equity', 'code' => 'EQ', 'classification' => 'equity', 'normal_balance' => 'credit', 'display_order' => 20, 'is_system' => true],

            // Revenue
            ['name' => 'Income', 'code' => 'INC', 'classification' => 'revenue', 'normal_balance' => 'credit', 'display_order' => 30, 'is_system' => true],
            ['name' => 'Other Income', 'code' => 'OI', 'classification' => 'revenue', 'normal_balance' => 'credit', 'display_order' => 31, 'is_system' => true],

            // Expenses
            ['name' => 'Cost of Goods Sold', 'code' => 'COGS', 'classification' => 'expense', 'normal_balance' => 'debit', 'display_order' => 40, 'is_system' => true],
            ['name' => 'Expense', 'code' => 'EXP', 'classification' => 'expense', 'normal_balance' => 'debit', 'display_order' => 41, 'is_system' => true],
            ['name' => 'Other Expense', 'code' => 'OE', 'classification' => 'expense', 'normal_balance' => 'debit', 'display_order' => 42, 'is_system' => true],
        ];

        foreach ($accountTypes as $type) {
            DB::table('account_types')->insert(array_merge($type, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Account subtypes
        $subtypes = [
            // Bank subtypes
            ['account_type_code' => 'BANK', 'name' => 'Checking', 'code' => 'CHECKING'],
            ['account_type_code' => 'BANK', 'name' => 'Savings', 'code' => 'SAVINGS'],
            ['account_type_code' => 'BANK', 'name' => 'Money Market', 'code' => 'MONEY_MARKET'],
            ['account_type_code' => 'BANK', 'name' => 'Cash on Hand', 'code' => 'CASH'],

            // AR subtypes
            ['account_type_code' => 'AR', 'name' => 'Accounts Receivable', 'code' => 'AR_TRADE'],

            // Other Current Asset subtypes
            ['account_type_code' => 'OCA', 'name' => 'Inventory', 'code' => 'INVENTORY'],
            ['account_type_code' => 'OCA', 'name' => 'Prepaid Expenses', 'code' => 'PREPAID'],
            ['account_type_code' => 'OCA', 'name' => 'Undeposited Funds', 'code' => 'UNDEPOSITED'],
            ['account_type_code' => 'OCA', 'name' => 'Employee Advances', 'code' => 'EMP_ADVANCES'],

            // Fixed Asset subtypes
            ['account_type_code' => 'FA', 'name' => 'Buildings', 'code' => 'BUILDINGS'],
            ['account_type_code' => 'FA', 'name' => 'Furniture & Equipment', 'code' => 'EQUIPMENT'],
            ['account_type_code' => 'FA', 'name' => 'Vehicles', 'code' => 'VEHICLES'],
            ['account_type_code' => 'FA', 'name' => 'Accumulated Depreciation', 'code' => 'ACCUM_DEPR'],
            ['account_type_code' => 'FA', 'name' => 'Land', 'code' => 'LAND'],

            // AP subtypes
            ['account_type_code' => 'AP', 'name' => 'Accounts Payable', 'code' => 'AP_TRADE'],

            // Credit Card subtypes
            ['account_type_code' => 'CC', 'name' => 'Credit Card', 'code' => 'CREDIT_CARD'],

            // Other Current Liability subtypes
            ['account_type_code' => 'OCL', 'name' => 'Payroll Liabilities', 'code' => 'PAYROLL_LIAB'],
            ['account_type_code' => 'OCL', 'name' => 'Sales Tax Payable', 'code' => 'SALES_TAX'],
            ['account_type_code' => 'OCL', 'name' => 'Deferred Revenue', 'code' => 'DEFERRED_REV'],
            ['account_type_code' => 'OCL', 'name' => 'Customer Deposits', 'code' => 'CUST_DEPOSITS'],
            ['account_type_code' => 'OCL', 'name' => 'Line of Credit', 'code' => 'LOC'],

            // Long Term Liability subtypes
            ['account_type_code' => 'LTL', 'name' => 'Notes Payable', 'code' => 'NOTES_PAYABLE'],
            ['account_type_code' => 'LTL', 'name' => 'Loans Payable', 'code' => 'LOANS_PAYABLE'],

            // Equity subtypes
            ['account_type_code' => 'EQ', 'name' => 'Common Stock', 'code' => 'COMMON_STOCK'],
            ['account_type_code' => 'EQ', 'name' => 'Retained Earnings', 'code' => 'RETAINED_EARN'],
            ['account_type_code' => 'EQ', 'name' => 'Owner\'s Equity', 'code' => 'OWNERS_EQUITY'],
            ['account_type_code' => 'EQ', 'name' => 'Partner Distributions', 'code' => 'DISTRIBUTIONS'],
            ['account_type_code' => 'EQ', 'name' => 'Opening Balance Equity', 'code' => 'OBE'],

            // Income subtypes
            ['account_type_code' => 'INC', 'name' => 'Sales', 'code' => 'SALES'],
            ['account_type_code' => 'INC', 'name' => 'Service Revenue', 'code' => 'SERVICE'],
            ['account_type_code' => 'INC', 'name' => 'Discounts Given', 'code' => 'DISCOUNTS_GIVEN'],

            // Other Income subtypes
            ['account_type_code' => 'OI', 'name' => 'Interest Income', 'code' => 'INTEREST_INC'],
            ['account_type_code' => 'OI', 'name' => 'Other Income', 'code' => 'OTHER_INC'],

            // COGS subtypes
            ['account_type_code' => 'COGS', 'name' => 'Supplies & Materials', 'code' => 'SUPPLIES_COGS'],
            ['account_type_code' => 'COGS', 'name' => 'Cost of Labor', 'code' => 'LABOR_COGS'],

            // Expense subtypes
            ['account_type_code' => 'EXP', 'name' => 'Advertising', 'code' => 'ADVERTISING'],
            ['account_type_code' => 'EXP', 'name' => 'Bank Charges', 'code' => 'BANK_CHARGES'],
            ['account_type_code' => 'EXP', 'name' => 'Insurance', 'code' => 'INSURANCE'],
            ['account_type_code' => 'EXP', 'name' => 'Office Expenses', 'code' => 'OFFICE'],
            ['account_type_code' => 'EXP', 'name' => 'Payroll Expenses', 'code' => 'PAYROLL'],
            ['account_type_code' => 'EXP', 'name' => 'Professional Fees', 'code' => 'PROF_FEES'],
            ['account_type_code' => 'EXP', 'name' => 'Rent Expense', 'code' => 'RENT'],
            ['account_type_code' => 'EXP', 'name' => 'Travel', 'code' => 'TRAVEL'],
            ['account_type_code' => 'EXP', 'name' => 'Utilities', 'code' => 'UTILITIES'],
            ['account_type_code' => 'EXP', 'name' => 'Depreciation', 'code' => 'DEPRECIATION'],
            ['account_type_code' => 'EXP', 'name' => 'Taxes & Licenses', 'code' => 'TAXES'],
            ['account_type_code' => 'EXP', 'name' => 'Meals & Entertainment', 'code' => 'MEALS'],

            // Other Expense subtypes
            ['account_type_code' => 'OE', 'name' => 'Interest Expense', 'code' => 'INTEREST_EXP'],
            ['account_type_code' => 'OE', 'name' => 'Other Expense', 'code' => 'OTHER_EXP'],
        ];

        $accountTypeIds = DB::table('account_types')->pluck('id', 'code');

        $order = 1;
        foreach ($subtypes as $subtype) {
            DB::table('account_subtypes')->insert([
                'account_type_id' => $accountTypeIds[$subtype['account_type_code']],
                'name' => $subtype['name'],
                'code' => $subtype['code'],
                'display_order' => $order++,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
