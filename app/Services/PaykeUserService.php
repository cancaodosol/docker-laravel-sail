<?php

namespace App\Services;

use App\Models\PaykeHost;
use App\Models\PaykeResource;
use App\Models\PaykeDb;
use App\Models\PaykeUser;
use App\Services\DeployService;
use App\Services\DeployLogService;

class PaykeUserService
{
    public function save_init(PaykeUser $user): void
    {
        $user->status = PaykeUser::STATUS__BEFORE_SETTING;
        $user->PaykeHost->status = PaykeHost::STATUS__READY;
        $user->PaykeDb->status = PaykeDb::STATUS__IN_USE;
        $user->save();
        $user->PaykeHost->save();
        $user->PaykeDb->save();
    }

    public function edit(int $id, PaykeUser $newUser): void
    {
        $currentUser = $this->find_by_id($id);

        $logService = new DeployLogService();
        $deployService = new DeployService();
        if($newUser->status != $currentUser->status)
        {
            // ログ：ステータスを〇〇から、〇〇へ変更しました。
            $currentStatusName = PaykeUser::STATUS_NAMES[$currentUser->status];
            $newStatusName = PaykeUser::STATUS_NAMES[$newUser->status];
            $message = "ステータスを「{$currentStatusName}」から「{$newStatusName}」へ変更しました。";
            $logService->write_other_log($currentUser, "ステータス変更（手動）", $message);
        }

        if($newUser->payke_resource_id != $currentUser->payke_resource_id)
        {
            // ログ：Paykeバージョンを〇〇から、〇〇へ変更しました。
            $service = new PaykeResourceService();
            $currentVersion = $service->get_version_by_id($currentUser->payke_resource_id);
            $newVersion = $service->get_version_by_id($newUser->payke_resource_id);
            $message = "Paykeバージョンを「{$currentVersion}」から「{$newVersion}」へ変更しました。";
            $logService->write_other_log($currentUser, "バージョン変更（手動）", $message);
        }

        if($newUser->user_app_name != $currentUser->user_app_name)
        {
            // 処理：Payke環境への適用
            $log = [];
            $old_app_name = $currentUser->user_app_name;
            $new_app_name = $newUser->user_app_name;
            $is_success = $deployService->rename_app_name($currentUser, $old_app_name, $new_app_name, $log);
            // MEMO: 更新失敗の時は、元の状態に戻す。
            if(!$is_success)
            {
                $newUser->user_app_name = $old_app_name;
                $newUser->status = PaykeUser::STATUS__HAS_ERROR;
            }
        }

        if($newUser->enable_affiliate != $currentUser->enable_affiliate)
        {
            if($newUser->enable_affiliate) // true: 1
            {
                $log = [];
                $is_success = $deployService->open_affiliate($currentUser->PaykeHost, $currentUser, $log);
                // MEMO: 更新失敗の時は、元の状態に戻す。
                if(!$is_success)
                {
                    $newUser->enable_affiliate = 0;
                    $newUser->status = PaykeUser::STATUS__HAS_ERROR;
                }
            }
            else
            {
                $log = [];
                $is_success = $deployService->close_affiliate($currentUser->PaykeHost, $currentUser, $log);
                // MEMO: 更新失敗の時は、元の状態に戻す。
                if(!$is_success)
                {
                    $newUser->enable_affiliate = 1;
                    $newUser->status = PaykeUser::STATUS__HAS_ERROR;
                }
            }
        }

        $currentUser->update([
            "status" => $newUser->status
            ,"payke_resource_id" => $newUser->payke_resource_id
            ,"user_app_name" => $newUser->user_app_name
            ,"app_url" => $newUser->app_url
            ,"enable_affiliate" => $newUser->enable_affiliate
            ,"user_name" => $newUser->user_name
            ,"email_address" => $newUser->email_address
            ,"memo" => $newUser->memo
        ]);
    }

    public function open_affiliate(PaykeUser $user): void
    {
        $dService = new DeployService();
        $log = [];
        $is_success = $dService->open_affiliate($user->PaykeHost, $user, $log);
        if($is_success)
        {
            $user->enable_affiliate = 1; // true: 1
            $user->save();
        }
        else
        {
            $this->save_has_error($user, "アフィリエイト機能の有効化に失敗しました。");
        }
    }

    public function close_affiliate(PaykeUser $user): void
    {
        $dService = new DeployService();
        $log = [];
        $is_success = $dService->close_affiliate($user->PaykeHost, $user, $log);
        if($is_success)
        {
            $user->enable_affiliate = 0; // false: 1
            $user->save();
        }
        else
        {
            $this->save_has_error($user, "アフィリエイト機能の無効化に失敗しました。");
        }
    }

    public function save_active(PaykeUser $user): void
    {
        $user->status = PaykeUser::STATUS__ACTIVE;
        $user->save();
    }

    public function save_has_error(PaykeUser $user, string $error): void
    {
        $user->status = PaykeUser::STATUS__HAS_ERROR;
        $user->save();
    }

    public function save_updating_now(PaykeUser $user): void
    {
        $user->status = PaykeUser::STATUS__UPDATING_NOW;
        $user->save();
    }

    public function save_update_waiting(PaykeUser $user): void
    {
        $user->status = PaykeUser::STATUS__UPDATE_WAITING;
        $user->save();
    }

    public function find_all()
    {
        return PaykeUser::all();
    }

    public function find_by_id(int $id)
    {
        return PaykeUser::where('id', $id)->firstOrFail();
    }

    public function find_by_ids(array $ids)
    {
        return PaykeUser::whereIn('id', $ids)->get();
    }

    public function exists_same_name(string $name)
    {
        return PaykeUser::where('user_app_name', $name)->count() > 0;
    }

    public function get_statuses()
    {
        $statuses = [
            ["id" => PaykeUser::STATUS__ACTIVE, "name" => "正常稼働"],
            ["id" => PaykeUser::STATUS__DISABLE_ADMIN, "name" => "管理停止"],
            ["id" => PaykeUser::STATUS__DISABLE_ADMIN_AND_SALES, "name" => "管理・販売停止"],
            ["id" => PaykeUser::STATUS__DELETE, "name" => "削除済"],
            ["id" => PaykeUser::STATUS__HAS_ERROR, "name" => "エラーあり"],
            ["id" => PaykeUser::STATUS__BEFORE_SETTING, "name" => "初回登録"],
            ["id" => PaykeUser::STATUS__UPDATE_WAITING, "name" => "アップデート待ち"],
            ["id" => PaykeUser::STATUS__UPDATING_NOW, "name" => "アップデート処理中"]
        ];
        return $statuses;
    }
}