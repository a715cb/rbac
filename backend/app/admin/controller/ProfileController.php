<?php
namespace app\admin\controller;

use app\model\User as UserModel;
use app\model\Role;
use app\model\Department;
use app\common\AdminAuth;
use app\admin\validate\ProfileValidate;
use think\Request;

class ProfileController extends BaseController
{
    public function show(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return $this->error('用户未登录', 401);
        }

        $user = UserModel::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $userData = $user->toArray();
        unset($userData['password']);

        $roles = (new Role())->getUserRoles($userId);
        $userData['roles'] = $roles;

        if (!empty($userData['dept_id'])) {
            $dept = Department::find($userData['dept_id']);
            $userData['dept_name'] = $dept ? $dept->name : '';
        } else {
            $userData['dept_name'] = '';
        }

        return $this->success($userData, '获取成功');
    }

    public function update(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return $this->error('用户未登录', 401);
        }

        $user = UserModel::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new ProfileValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (!empty($data['email']) && UserModel::withTrashed()->where('email', $data['email'])->where('id', '<>', $userId)->find()) {
            return $this->error('邮箱已存在', 422);
        }

        if (!empty($data['mobile']) && UserModel::withTrashed()->where('mobile', $data['mobile'])->where('id', '<>', $userId)->find()) {
            return $this->error('手机号已存在', 422);
        }

        try {
            if (isset($data['nickname'])) $user->nickname = $data['nickname'];
            if (isset($data['email'])) $user->email = $data['email'];
            if (isset($data['mobile'])) $user->mobile = $data['mobile'];
            if (isset($data['gender'])) $user->gender = (int) $data['gender'];

            $user->save();

            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新个人信息失败：' . $e->getMessage());
        }
    }

    public function uploadAvatar(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return $this->error('用户未登录', 401);
        }

        $user = UserModel::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $file = $request->file('avatar');
        if (!$file) {
            return $this->error('请上传头像文件', 422);
        }

        try {
            $ext = strtolower($file->getOriginalExtension());
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedExts)) {
                return $this->error('头像仅支持 jpg/jpeg/png/gif/webp 格式', 422);
            }

            $maxSize = 2 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return $this->error('头像文件大小不能超过2MB', 422);
            }

            $saveName = $file->hashName();
            $file->move(public_path() . 'storage' . DIRECTORY_SEPARATOR . 'avatar', $saveName);

            $avatarUrl = '/storage/avatar/' . str_replace('\\', '/', $saveName);

            $user->avatar = $avatarUrl;
            $user->save();

            return $this->success([
                'avatar' => $avatarUrl,
            ], '头像上传成功');
        } catch (\Exception $e) {
            return $this->error('头像上传失败：' . $e->getMessage());
        }
    }

    public function changePassword(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return $this->error('用户未登录', 401);
        }

        $user = UserModel::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new ProfileValidate();
            $validate->scene('change_password')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (!password_verify($data['old_password'], $user->password)) {
            return $this->error('原密码错误', 400);
        }

        try {
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            $user->save();

            $auth = AdminAuth::instance();
            $auth->setUser($userId);
            $auth->clearCache();
            return $this->success([], '密码修改成功');
        } catch (\Exception $e) {
            return $this->error('密码修改失败：' . $e->getMessage());
        }
    }
}