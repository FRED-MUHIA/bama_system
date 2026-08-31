<?php

namespace Tests\Unit;

use Tests\TestCase;

class IndustryExpenseCapabilitiesTest extends TestCase
{
    public function test_every_industry_exposes_shared_expense_capabilities(): void
    {
        foreach (config('industry-packages.industries') as $industry) {
            $label = $industry['name'];

            $this->assertContains('Expense Management', $industry['features'], $label.' should list expense management.');
            $this->assertContains('Expense Management', $industry['modules'], $label.' should include the expense module.');
            $this->assertContains('Budget Tracking', $industry['modules'], $label.' should include budget tracking.');
            $this->assertContains('Expense Report', $industry['reports'], $label.' should include expense reports.');
            $this->assertContains('Record Expense', $industry['workflows'], $label.' should include expense workflows.');
            $this->assertContains('Expense claim', $industry['templates'], $label.' should include expense templates.');
            $this->assertContains('expenses.view', $industry['permissions'], $label.' should include expense permissions.');
            $this->assertContains('Expenses', $industry['menu_structure'], $label.' should include expenses in menu structure.');
        }
    }
}
