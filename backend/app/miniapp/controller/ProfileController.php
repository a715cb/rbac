<?php
namespace app\miniapp\controller;

use app\model\WxUser;
use think\Request;

class ProfileController extends MiniappBaseController
{
    public function show(Request $request)
    {
        $wxUserId = $request->wxUser['id'] ?? 0;
        $wxUser = WxUser::find($wxUserId);

        if (!$wxUser) {
            return $this->error('用户不存在', 404);
        }

        return $this->success([
            'id' => $wxUser->id,
            'nickname' => $wxUser->nickname,
            'avatar' => $wxUser->avatar,
            'phone' => $wxUser->phone,
            'gender' => $wxUser->gender,
            'sys_user_id' => $wxUser->sys_user_id,
            'is_linked' => !empty($wxUser->sys_user_id),
        ]);
    }

    public function update(Request $request)
    {
        $wxUserId = $request->wxUser['id'] ?? 0;
        $wxUser = WxUser::find($wxUserId);

        if (!$wxUser) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->put();

        if (isset($data['nickname'])) {
            $wxUser->nickname = $data['nickname'];
        }
        if (isset($data['gender'])) {
            $wxUser->gender = (int) $data['gender'];
        }

        $wxUser->save();

        return $this->success([
            'id' => $wxUser->id,
            'nickname' => $wxUser->nickname,
            'avatar' => $wxUser->avatar,
            'phone' => $wxUser->phone,
            'gender' => $wxUser->gender,
            'sys_user_id' => $wxUser->sys_user_id,
            'is_linked' => !empty($wxUser->sys_user_id),
        ], '更新成功');
    }

    public function uploadAvatar(Request $request)
    {
        $wxUserId = $request->wxUser['id'] ?? 0;
        $wxUser = WxUser::find($wxUserId);

        if (!$wxUser) {
            return $this->error('用户不存在', 404);
        }

        $file = $request->file('avatar');
        if (!$file) {
            return $this->error('请上传头像文件', 400);
        }

        try {
            $saveName = \think\facade\Filesystem::disk('public')->putFile('avatar', $file);
            $avatarUrl = '/storage/' . $saveName;

            $wxUser->avatar = $avatarUrl;
            $wxUser->save();

            return $this->success([
                'avatar' => $avatarUrl,
            ], '头像上传成功');
        } catch (\Exception $e) {
            return $this->error('头像上传失败：' . $e->getMessage(), 400);
        }
    }
}
