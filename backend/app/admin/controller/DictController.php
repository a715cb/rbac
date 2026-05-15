<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\model\DictType as DictTypeModel;
use app\model\DictData as DictDataModel;
use app\admin\validate\DictTypeValidate;
use app\admin\validate\DictDataValidate;
use think\Request;
use think\facade\Db;

class DictController extends BaseController
{
    public function typeList(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');

        $where = [];
        if (!empty($keyword)) {
            $where[] = ['name|code', 'like', "%{$keyword}%"];
        }
        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        $list = DictTypeModel::where($where)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list, '获取成功');
    }

    public function typeDetail(int $id)
    {
        $dictType = DictTypeModel::find($id);
        if (!$dictType) {
            return $this->error('字典类型不存在', 404);
        }
        return $this->success($dictType->toArray(), '获取成功');
    }

    public function typeStore(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new DictTypeValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (DictTypeModel::where('code', $data['code'])->find()) {
            return $this->error('字典编码已存在', 422);
        }

        try {
            $dictType = new DictTypeModel();
            $dictType->name = $data['name'];
            $dictType->code = $data['code'];
            $dictType->type = $data['type'] ?? 'string';
            $dictType->status = (int) ($data['status'] ?? 1);
            $dictType->sort = (int) ($data['sort'] ?? 0);
            $dictType->remark = $data['remark'] ?? '';
            $dictType->created_by = $request->userInfo['id'] ?? null;
            $dictType->save();

            return $this->success(['id' => $dictType->id], '创建成功');
        } catch (\Exception $e) {
            return $this->error('创建字典类型失败：' . $e->getMessage());
        }
    }

    public function typeUpdate(Request $request, int $id)
    {
        $dictType = DictTypeModel::find($id);
        if (!$dictType) {
            return $this->error('字典类型不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new DictTypeValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (isset($data['code']) && $data['code'] != $dictType->code) {
            if (DictTypeModel::where('code', $data['code'])->where('id', '<>', $id)->find()) {
                return $this->error('字典编码已存在', 422);
            }
        }

        try {
            if (isset($data['name'])) $dictType->name = $data['name'];
            if (isset($data['code'])) $dictType->code = $data['code'];
            if (isset($data['type'])) $dictType->type = $data['type'];
            if (isset($data['status'])) $dictType->status = (int) $data['status'];
            if (isset($data['sort'])) $dictType->sort = (int) $data['sort'];
            if (isset($data['remark'])) $dictType->remark = $data['remark'];
            $dictType->updated_by = $request->userInfo['id'] ?? null;
            $dictType->save();

            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新字典类型失败：' . $e->getMessage());
        }
    }

    public function typeDestroy(int $id)
    {
        $dictType = DictTypeModel::find($id);
        if (!$dictType) {
            return $this->error('字典类型不存在', 404);
        }

        try {
            Db::transaction(function () use ($id) {
                $dataIds = DictDataModel::where('dict_type_id', $id)
                    ->column('id');
                if (!empty($dataIds)) {
                    DictDataModel::destroy($dataIds);
                }
                DictTypeModel::destroy($id);
            });
            return $this->success([], '删除成功');
        } catch (\think\db\exception\DbException $e) {
            return $this->error('删除字典类型失败：' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->error('删除字典类型失败：系统错误');
        }
    }

    public function typeChangeStatus(Request $request, int $id)
    {
        $dictType = DictTypeModel::find($id);
        if (!$dictType) {
            return $this->error('字典类型不存在', 404);
        }

        $data = $request->put();
        try {
            $validate = new DictTypeValidate();
            $validate->scene('change_status')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $dictType->status = (int) $data['status'];
        $dictType->updated_by = $request->userInfo['id'] ?? null;
        $dictType->save();

        return $this->success([], $data['status'] == 1 ? '字典类型已启用' : '字典类型已禁用');
    }

    public function dataList(Request $request)
    {
        $dictTypeId = $request->get('dict_type_id');
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');

        if (empty($dictTypeId)) {
            return $this->error('字典类型ID不能为空', 422);
        }

        $where = [['dict_type_id', '=', (int) $dictTypeId]];
        if (!empty($keyword)) {
            $where[] = ['label|value', 'like', "%{$keyword}%"];
        }
        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        $list = DictDataModel::where($where)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list, '获取成功');
    }

    public function dataDetail(int $id)
    {
        $dictData = DictDataModel::find($id);
        if (!$dictData) {
            return $this->error('字典数据不存在', 404);
        }
        return $this->success($dictData->toArray(), '获取成功');
    }

    public function dataStore(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new DictDataValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $dictTypeId = (int) $data['dict_type_id'];
        $dictType = DictTypeModel::find($dictTypeId);
        if (!$dictType) {
            return $this->error('字典类型不存在', 404);
        }

        if (DictDataModel::where('dict_type_id', $dictTypeId)->where('value', $data['value'])->find()) {
            return $this->error('该字典类型下键值已存在', 422);
        }

        try {
            $dictData = new DictDataModel();
            $dictData->dict_type_id = $dictTypeId;
            $dictData->label = $data['label'];
            $dictData->value = $data['value'];
            $dictData->status = (int) ($data['status'] ?? 1);
            $dictData->sort = (int) ($data['sort'] ?? 0);
            $dictData->remark = $data['remark'] ?? '';
            $dictData->created_by = $request->userInfo['id'] ?? null;
            $dictData->save();

            return $this->success(['id' => $dictData->id], '创建成功');
        } catch (\Exception $e) {
            return $this->error('创建字典数据失败：' . $e->getMessage());
        }
    }

    public function dataUpdate(Request $request, int $id)
    {
        $dictData = DictDataModel::find($id);
        if (!$dictData) {
            return $this->error('字典数据不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new DictDataValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (isset($data['value']) && $data['value'] != $dictData->value) {
            if (DictDataModel::where('dict_type_id', $dictData->dict_type_id)->where('value', $data['value'])->where('id', '<>', $id)->find()) {
                return $this->error('该字典类型下键值已存在', 422);
            }
        }

        try {
            if (isset($data['label'])) $dictData->label = $data['label'];
            if (isset($data['value'])) $dictData->value = $data['value'];
            if (isset($data['status'])) $dictData->status = (int) $data['status'];
            if (isset($data['sort'])) $dictData->sort = (int) $data['sort'];
            if (isset($data['remark'])) $dictData->remark = $data['remark'];
            $dictData->updated_by = $request->userInfo['id'] ?? null;
            $dictData->save();

            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新字典数据失败：' . $e->getMessage());
        }
    }

    public function dataDestroy(int $id)
    {
        $dictData = DictDataModel::find($id);
        if (!$dictData) {
            return $this->error('字典数据不存在', 404);
        }

        try {
            DictDataModel::destroy($id);
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除字典数据失败：' . $e->getMessage());
        }
    }

    public function dataChangeStatus(Request $request, int $id)
    {
        $dictData = DictDataModel::find($id);
        if (!$dictData) {
            return $this->error('字典数据不存在', 404);
        }

        $data = $request->put();
        try {
            $validate = new DictDataValidate();
            $validate->scene('change_status')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $dictData->status = (int) $data['status'];
        $dictData->updated_by = $request->userInfo['id'] ?? null;
        $dictData->save();

        return $this->success([], $data['status'] == 1 ? '字典数据已启用' : '字典数据已禁用');
    }

    public function dataUpdateSort(Request $request)
    {
        $data = $request->post();
        if (!is_array($data)) {
            return $this->error('参数格式错误', 422);
        }

        try {
            Db::transaction(function () use ($data) {
                foreach ($data as $item) {
                    if (!isset($item['id'])) {
                        throw new \Exception('缺少ID参数');
                    }
                    DictDataModel::where('id', $item['id'])->update(['sort' => $item['sort'] ?? 0]);
                }
            });
            return $this->success([], '排序更新成功');
        } catch (\Exception $e) {
            return $this->error('排序更新失败：' . $e->getMessage());
        }
    }

    public function dictByCode(Request $request, string $code = '')
    {
        if (empty($code)) {
            return $this->error('字典编码不能为空', 422);
        }

        $dictType = DictTypeModel::where('code', $code)->where('status', 1)->find();
        if (!$dictType) {
            return $this->error('字典类型不存在或已禁用', 404);
        }

        $limitInput = $request->get('limit');
        if ($limitInput !== null && $limitInput !== '') {
            if (!is_numeric($limitInput)) {
                return $this->error('limit参数必须为正整数', 422);
            }
            $limit = (int) $limitInput;
            if ($limit < 1 || $limit > 500) {
                return $this->error('limit参数必须在1-500之间', 422);
            }
        } else {
            $limit = 200;
        }

        $list = DictDataModel::where('dict_type_id', $dictType->id)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();

        $options = array_map(function ($item) {
            return [
                'value' => $item['value'],
                'label' => $item['label'],
            ];
        }, $list);

        return $this->success($options, '获取成功');
    }
}
