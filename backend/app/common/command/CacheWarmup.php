<?php
/**
 * @文件: CacheWarmup.php
 * @用途: 权限缓存预热命令，主动重建全局和用户级权限缓存
 * @描述: 通过 CLI 或定时任务触发，提前重建全局缓存（all_menu_codes、all_api_codes、all_button_codes）
 *        和指定用户的权限缓存（user_menu_codes、user_api_codes、user_button_codes、user_menu_tree），
 *        避免缓存过期后的首次请求延迟或回填失败导致权限系统异常
 * @核心逻辑:
 *   1. 清除旧的全局权限缓存和用户级缓存
 *   2. 依次重建 all_menu_codes、all_api_codes、all_button_codes
 *   3. 可选重建指定用户的权限缓存（通过 --user 参数）
 *   4. 验证重建结果，输出每个缓存键的状态
 *   5. 可通过 cron 定时执行，在缓存过期前主动刷新
 */
namespace app\common\command;

use app\common\AdminAuth;
use app\common\SimpleCache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Config;
use think\facade\Db;

class CacheWarmup extends Command
{
    protected function configure(): void
    {
        $this->setName('cache:warmup')
            ->addArgument('user')
            ->setDescription('预热权限缓存：重建全局和用户级权限码缓存（可选指定用户ID）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('<info>开始预热权限缓存...</info>');

        AdminAuth::clearGlobalCache();
        $output->writeln('<comment>已清除旧的全局权限缓存</comment>');

        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        $results = [
            'all_menu_codes' => $this->warmupMenuCodes($cacheTime),
            'all_api_codes' => $this->warmupApiCodes($cacheTime),
            'all_button_codes' => $this->warmupButtonCodes($cacheTime),
        ];

        $hasError = false;
        foreach ($results as $key => $result) {
            if ($result === null) {
                $output->writeln("<error>  ✗ {$key}：重建失败（回调返回 null）</error>");
                $hasError = true;
            } else {
                $count = is_array($result) ? count($result) : 0;
                $output->writeln("<info>  ✓ {$key}：重建成功（{$count} 条记录）</info>");
            }
        }

        $userId = $input->getArgument('user');
        if ($userId !== null) {
            $userId = (int) $userId;
            if ($userId <= 0) {
                $output->writeln('<error>用户ID必须为正整数</error>');
                return 1;
            }

            $output->writeln("<comment>预热用户 {$userId} 的权限缓存...</comment>");
            $userResults = $this->warmupUserCache($userId, $cacheTime);

            foreach ($userResults as $key => $result) {
                if ($result === null) {
                    $output->writeln("<error>  ✗ {$key}：重建失败</error>");
                    $hasError = true;
                } else {
                    $count = is_array($result) ? count($result) : 0;
                    $output->writeln("<info>  ✓ {$key}：重建成功（{$count} 条记录）</info>");
                }
            }
        }

        if ($hasError) {
            $output->writeln('<error>部分缓存预热失败，请检查数据库连接和数据完整性</error>');
            return 1;
        }

        $output->writeln('<info>权限缓存预热完成</info>');
        return 0;
    }

    private function warmupMenuCodes(int $cacheTime): ?array
    {
        try {
            $data = (new \app\model\Menu())->where('status', 1)->column('code');
            SimpleCache::set('all_menu_codes', $data, $cacheTime);
            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function warmupApiCodes(int $cacheTime): ?array
    {
        try {
            $data = Db::name('api')
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('code');
            SimpleCache::set('all_api_codes', $data, $cacheTime);
            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function warmupButtonCodes(int $cacheTime): ?array
    {
        try {
            $data = Db::name('menu_button')
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('code');
            SimpleCache::set('all_button_codes', $data, $cacheTime);
            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function warmupUserCache(int $userId, int $cacheTime): array
    {
        $results = [];

        try {
            $auth = AdminAuth::instance();
            $auth->setUser($userId);

            $menuCodes = $auth->getMenuCodes();
            SimpleCache::set('user_menu_codes_' . $userId, $menuCodes, $cacheTime);
            $results['user_menu_codes_' . $userId] = $menuCodes;

            $apiCodes = $auth->getApiCodes();
            SimpleCache::set('user_api_codes_' . $userId, $apiCodes, $cacheTime);
            $results['user_api_codes_' . $userId] = $apiCodes;

            $buttonCodes = $auth->getButtonCodes();
            SimpleCache::set('user_button_codes_' . $userId, $buttonCodes, $cacheTime);
            $results['user_button_codes_' . $userId] = $buttonCodes;

            $menuTree = $auth->getMenuTree();
            SimpleCache::set('user_menu_tree_' . $userId, $menuTree, $cacheTime);
            $results['user_menu_tree_' . $userId] = $menuTree;
        } catch (\Throwable $e) {
            $results['error'] = null;
        }

        return $results;
    }
}
