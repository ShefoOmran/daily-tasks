<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\User;
use App\TransactionType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $free = Subscription::query()->updateOrCreate(
            ['name' => 'المجانية'],
            [
                'price' => 0,
                'daily_task_limit' => 5,
                'reward_amount' => 0.50,
                'duration_minutes' => 30,
                'is_free' => true,
            ],
        );

        Subscription::query()->updateOrCreate(
            ['name' => 'الأساسية'],
            [
                'price' => 10,
                'daily_task_limit' => 15,
                'reward_amount' => 1,
                'duration_minutes' => 20,
                'is_free' => false,
            ],
        );

        Subscription::query()->updateOrCreate(
            ['name' => 'المتقدمة'],
            [
                'price' => 25,
                'daily_task_limit' => 30,
                'reward_amount' => 2,
                'duration_minutes' => 10,
                'is_free' => false,
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['name' => 'تحويل بنكي', 'type' => TransactionType::Deposit],
            ['details' => 'اسم الحساب: المهام اليومية. أرسل إيصال التحويل للوكيل.', 'is_active' => true],
        );

        PaymentMethod::query()->updateOrCreate(
            ['name' => 'محفظة', 'type' => TransactionType::Withdrawal],
            ['details' => 'أدخل المبلغ وسيحوّله الوكيل إلى المحفظة المسجّلة.', 'is_active' => true],
        );

        // غيّر الرقم ده لرقم الوكيل الحقيقي لأنه بيظهر للمستخدمين
        Agent::query()->updateOrCreate(
            ['phone' => '01000000000'],
            ['name' => 'وكيل القاهرة', 'note' => 'متاح يومياً من 10 صباحاً حتى 10 مساءً', 'is_active' => true],
        );

        // الحسابات التجريبية للبيئة المحلية فقط (باسورد معروف، ماتتعملش على الإنتاج)
        if (app()->environment('local')) {
            User::query()->updateOrCreate(
                ['email' => 'admin@daily-tasks.test'],
                [
                    'name' => 'مدير النظام',
                    'password' => 'password',
                    'is_admin' => true,
                    'balance' => 0,
                    'subscription_id' => null,
                ],
            );

            User::query()->updateOrCreate(
                ['email' => 'user@daily-tasks.test'],
                [
                    'name' => 'مستخدم تجريبي',
                    'password' => 'password',
                    'is_admin' => false,
                    'balance' => 20,
                    'subscription_id' => $free->id,
                ],
            );
        }
    }
}
