<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cto_requests', fn (Blueprint $table) => $table->timestamp('credits_posted_at')->nullable()->index());
        DB::table('cto_requests')->where('status', 'approved')->whereNotNull('cto_balance_after')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $posted = $row->credit_source === 'hr_direct' ? $row->created_at : null;
                if (!$posted && $row->approved_by_regionaldirector) {
                    $userId = DB::table('employees')->where('id', $row->approved_by_regionaldirector)->value('user_id');
                    if ($userId) $posted = DB::table('activity_audits')->where('user_id', $userId)->where('action', 'Approved CTO Request')
                        ->where('description', 'like', '%(#'.$row->id.')%')->min('created_at');
                }
                if (!$posted && $row->type === 'use') $posted = DB::table('coc_usages')->where('use_request_id', $row->id)->min('created_at');
                if ($posted) DB::table('cto_requests')->where('id', $row->id)->update(['credits_posted_at' => $posted]);
            }
        });
    }
    public function down(): void
    {
        Schema::table('cto_requests', fn (Blueprint $table) => $table->dropColumn('credits_posted_at'));
    }
};
