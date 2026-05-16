<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\common\SimpleCache;

/**
 * SimpleCache 统一缓存操作测试
 *
 * @测试目标: SimpleCache
 * @测试内容:
 *   - 基础 CRUD 操作（get/set/delete/has）
 *   - 原子递增（increment）
 *   - 条件写入（setIfNotExists）
 *   - 带互斥锁的缓存回填（remember）
 *   - TTL 抖动计算（getJitteredTtl）
 *   - 标签缓存操作（clearTag）
 *   - 边界条件和错误处理
 *
 * @覆盖场景:
 *   - 缓存命中/未命中
 *   - null 值缓存（优化后核心修复点）
 *   - TTL 为 0/正数/负数
 *   - increment 步长递增
 *   - setIfNotExists 并发语义
 *   - remember 回调执行与缓存回填
 *   - remember null 值回调结果缓存
 *
 * @注意事项:
 *   - 测试使用 ThinkPHP Cache 门面，依赖实际缓存驱动
 *   - 每个测试用例前后清理缓存键，确保测试隔离
 *   - remember 互斥锁测试不模拟并发，仅验证单进程逻辑
 */

class SimpleCacheTest extends TestCase
{
    private array $testCacheKeys = [];

    private string $keyPrefix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyPrefix = 'test_sc_' . uniqid() . '_';
        $this->cleanTestCache();
    }

    protected function tearDown(): void
    {
        $this->cleanTestCache();
        parent::tearDown();
    }

    private function makeKey(string $suffix): string
    {
        $key = $this->keyPrefix . $suffix;
        $this->testCacheKeys[] = $key;
        return $key;
    }

    private function cleanTestCache(): void
    {
        foreach ($this->testCacheKeys as $key) {
            SimpleCache::delete($key);
        }
        $this->testCacheKeys = [];
    }

    public function testSetAndGetBasicValue(): void
    {
        $key = $this->makeKey('basic');
        SimpleCache::set($key, 'hello');
        $this->assertEquals('hello', SimpleCache::get($key));
    }

    public function testGetReturnsDefaultOnMiss(): void
    {
        $key = $this->makeKey('miss');
        $this->assertNull(SimpleCache::get($key));
        $this->assertEquals('default', SimpleCache::get($key, 'default'));
        $this->assertEquals(0, SimpleCache::get($key, 0));
    }

    public function testSetWithZeroTtl(): void
    {
        $key = $this->makeKey('zero_ttl');
        $this->assertTrue(SimpleCache::set($key, 'persistent', 0));
        $this->assertEquals('persistent', SimpleCache::get($key));
    }

    public function testSetWithNegativeTtl(): void
    {
        $key = $this->makeKey('neg_ttl');
        $this->assertTrue(SimpleCache::set($key, 'value', -10));
        $this->assertEquals('value', SimpleCache::get($key));
    }

    public function testSetWithShortTtl(): void
    {
        $key = $this->makeKey('short_ttl');
        SimpleCache::set($key, 'expires', 1);
        $this->assertEquals('expires', SimpleCache::get($key));
    }

    public function testDeleteRemovesKey(): void
    {
        $key = $this->makeKey('del');
        SimpleCache::set($key, 'to_delete');
        $this->assertTrue(SimpleCache::delete($key));
        $this->assertNull(SimpleCache::get($key));
    }

    public function testDeleteNonexistentKeyReturnsFalse(): void
    {
        $key = $this->makeKey('del_miss');
        $this->assertFalse(SimpleCache::delete($key));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $key = $this->makeKey('has_exist');
        SimpleCache::set($key, 'exists');
        $this->assertTrue(SimpleCache::has($key));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $key = $this->makeKey('has_miss');
        $this->assertFalse(SimpleCache::has($key));
    }

    public function testSetAndGetNullValue(): void
    {
        $key = $this->makeKey('null_val');
        SimpleCache::set($key, null);
        $this->assertTrue(SimpleCache::has($key));
        $this->assertNull(SimpleCache::get($key));
    }

    public function testSetAndGetArrayValue(): void
    {
        $key = $this->makeKey('array_val');
        $data = ['foo' => 'bar', 'nested' => ['a' => 1]];
        SimpleCache::set($key, $data);
        $this->assertEquals($data, SimpleCache::get($key));
    }

    public function testSetAndGetIntegerValue(): void
    {
        $key = $this->makeKey('int_val');
        SimpleCache::set($key, 42);
        $this->assertEquals(42, SimpleCache::get($key));
    }

    public function testSetAndGetBooleanValue(): void
    {
        $key = $this->makeKey('bool_val');
        SimpleCache::set($key, false);
        $this->assertFalse(SimpleCache::get($key));
    }

    public function testIncrementFromZero(): void
    {
        $key = $this->makeKey('incr_zero');
        $result = SimpleCache::increment($key, 1, 60);
        $this->assertEquals(1, $result);
    }

    public function testIncrementMultipleTimes(): void
    {
        $key = $this->makeKey('incr_multi');
        SimpleCache::increment($key, 1, 60);
        SimpleCache::increment($key, 1, 60);
        $result = SimpleCache::increment($key, 1, 60);
        $this->assertEquals(3, $result);
    }

    public function testIncrementWithCustomStep(): void
    {
        $key = $this->makeKey('incr_step');
        $result = SimpleCache::increment($key, 5, 60);
        $this->assertEquals(5, $result);
        $result = SimpleCache::increment($key, 3, 60);
        $this->assertEquals(8, $result);
    }

    public function testIncrementWithZeroTtl(): void
    {
        $key = $this->makeKey('incr_no_ttl');
        $result = SimpleCache::increment($key, 1, 0);
        $this->assertEquals(1, $result);
    }

    public function testIncrementValueIsReadable(): void
    {
        $key = $this->makeKey('incr_read');
        SimpleCache::increment($key, 10, 60);
        $this->assertEquals(10, SimpleCache::get($key));
    }

    public function testSetIfNotExistsOnNewKey(): void
    {
        $key = $this->makeKey('sine_new');
        $result = SimpleCache::setIfNotExists($key, 'first', 60);
        $this->assertTrue($result);
        $this->assertEquals('first', SimpleCache::get($key));
    }

    public function testSetIfNotExistsOnExistingKey(): void
    {
        $key = $this->makeKey('sine_exist');
        SimpleCache::set($key, 'original');
        $result = SimpleCache::setIfNotExists($key, 'second', 60);
        $this->assertFalse($result);
        $this->assertEquals('original', SimpleCache::get($key));
    }

    public function testSetIfNotExistsWithZeroTtl(): void
    {
        $key = $this->makeKey('sine_no_ttl');
        $result = SimpleCache::setIfNotExists($key, 'value', 0);
        $this->assertTrue($result);
        $this->assertEquals('value', SimpleCache::get($key));
    }

    public function testSetIfNotExistsDoesNotOverwrite(): void
    {
        $key = $this->makeKey('sine_no_overwrite');
        SimpleCache::setIfNotExists($key, 'first', 60);
        SimpleCache::setIfNotExists($key, 'second', 60);
        $this->assertEquals('first', SimpleCache::get($key));
    }

    public function testRememberCachesCallbackResult(): void
    {
        $key = $this->makeKey('rem_cache');
        $callCount = 0;

        $result = SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return 'computed';
        });

        $this->assertEquals('computed', $result);
        $this->assertEquals(1, $callCount);

        SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return 'recomputed';
        });

        $this->assertEquals(1, $callCount);
        $this->assertEquals('computed', SimpleCache::get($key));
    }

    public function testRememberReturnsCachedOnHit(): void
    {
        $key = $this->makeKey('rem_hit');
        SimpleCache::set($key, 'preloaded');

        $result = SimpleCache::remember($key, 60, function () {
            return 'should_not_run';
        });

        $this->assertEquals('preloaded', $result);
    }

    public function testRememberCachesNullValue(): void
    {
        $key = $this->makeKey('rem_null');
        $callCount = 0;

        $result = SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return null;
        });

        $this->assertNull($result);
        $this->assertEquals(1, $callCount);

        SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return null;
        });

        $this->assertEquals(1, $callCount);
        $this->assertTrue(SimpleCache::has($key));
    }

    public function testRememberCachesArrayResult(): void
    {
        $key = $this->makeKey('rem_array');
        $data = ['menu' => ['home', 'user'], 'count' => 2];

        $result = SimpleCache::remember($key, 60, function () use ($data) {
            return $data;
        });

        $this->assertEquals($data, $result);
    }

    public function testRememberWithTag(): void
    {
        $key = $this->makeKey('rem_tag');
        $tag = 'test_tag_' . uniqid();

        SimpleCache::remember($key, 60, function () {
            return 'tagged_value';
        }, $tag);

        $this->assertEquals('tagged_value', SimpleCache::get($key));

        SimpleCache::clearTag($tag);
    }

    public function testRememberCallbackExceptionHandled(): void
    {
        $key = $this->makeKey('rem_exc');

        $result = SimpleCache::remember($key, 60, function () {
            throw new \RuntimeException('test error');
        });

        $this->assertNull($result);
    }

    public function testGetJitteredTtlReturnsPositiveValue(): void
    {
        $result = SimpleCache::getJitteredTtl(100);
        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(110, $result);
        $this->assertGreaterThanOrEqual(90, $result);
    }

    public function testGetJitteredTtlMinimumIsOne(): void
    {
        $result = SimpleCache::getJitteredTtl(1);
        $this->assertEquals(1, $result);
    }

    public function testGetJitteredTtlProducesVariance(): void
    {
        $values = [];
        for ($i = 0; $i < 100; $i++) {
            $values[] = SimpleCache::getJitteredTtl(1000);
        }
        $unique = array_unique($values);
        $this->assertGreaterThan(1, count($unique));
    }

    public function testClearTagRemovesTaggedEntries(): void
    {
        $tag = 'test_clear_tag_' . uniqid();
        $key1 = $this->makeKey('tag1');
        $key2 = $this->makeKey('tag2');

        \think\facade\Cache::tag($tag)->set($key1, 'tagged1', 60);
        \think\facade\Cache::tag($tag)->set($key2, 'tagged2', 60);

        $this->assertEquals('tagged1', SimpleCache::get($key1));
        $this->assertEquals('tagged2', SimpleCache::get($key2));

        SimpleCache::clearTag($tag);

        $this->assertNull(SimpleCache::get($key1));
        $this->assertNull(SimpleCache::get($key2));
    }

    public function testOverwriteExistingKey(): void
    {
        $key = $this->makeKey('overwrite');
        SimpleCache::set($key, 'original');
        SimpleCache::set($key, 'updated');
        $this->assertEquals('updated', SimpleCache::get($key));
    }

    public function testMultipleKeysAreIndependent(): void
    {
        $key1 = $this->makeKey('indep1');
        $key2 = $this->makeKey('indep2');

        SimpleCache::set($key1, 'value1');
        SimpleCache::set($key2, 'value2');

        $this->assertEquals('value1', SimpleCache::get($key1));
        $this->assertEquals('value2', SimpleCache::get($key2));

        SimpleCache::delete($key1);
        $this->assertNull(SimpleCache::get($key1));
        $this->assertEquals('value2', SimpleCache::get($key2));
    }

    public function testIncrementThenDelete(): void
    {
        $key = $this->makeKey('incr_del');
        SimpleCache::increment($key, 5, 60);
        $this->assertEquals(5, SimpleCache::get($key));

        SimpleCache::delete($key);
        $this->assertNull(SimpleCache::get($key));

        $result = SimpleCache::increment($key, 1, 60);
        $this->assertEquals(1, $result);
    }

    public function testSetIfNotExistsThenIncrement(): void
    {
        $key = $this->makeKey('sine_incr');
        SimpleCache::setIfNotExists($key, 0, 60);
        $result = SimpleCache::increment($key, 1, 60);
        $this->assertEquals(1, $result);
    }

    public function testRememberWithEmptyArrayResult(): void
    {
        $key = $this->makeKey('rem_empty_arr');
        $callCount = 0;

        $result = SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return [];
        });

        $this->assertEquals([], $result);
        $this->assertEquals(1, $callCount);

        SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return ['should_not'];
        });

        $this->assertEquals(1, $callCount);
    }

    public function testRememberWithZeroResult(): void
    {
        $key = $this->makeKey('rem_zero');
        $callCount = 0;

        $result = SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return 0;
        });

        $this->assertEquals(0, $result);
        $this->assertEquals(1, $callCount);

        SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return 999;
        });

        $this->assertEquals(1, $callCount);
    }

    public function testRememberWithFalseResult(): void
    {
        $key = $this->makeKey('rem_false');
        $callCount = 0;

        $result = SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return false;
        });

        $this->assertFalse($result);
        $this->assertEquals(1, $callCount);

        SimpleCache::remember($key, 60, function () use (&$callCount) {
            $callCount++;
            return true;
        });

        $this->assertEquals(1, $callCount);
    }

    public function testSetIfNotExistsWithNullValue(): void
    {
        $key = $this->makeKey('sine_null');
        $result = SimpleCache::setIfNotExists($key, null, 60);
        $this->assertTrue($result);
        $this->assertTrue(SimpleCache::has($key));

        $result2 = SimpleCache::setIfNotExists($key, 'overwrite', 60);
        $this->assertFalse($result2);
    }

    public function testSetIfNotExistsWithArrayValue(): void
    {
        $key = $this->makeKey('sine_arr');
        $data = ['a' => 1, 'b' => 2];
        SimpleCache::setIfNotExists($key, $data, 60);
        $this->assertEquals($data, SimpleCache::get($key));
    }

    public function testBulkSetAndDelete(): void
    {
        $keys = [];
        for ($i = 0; $i < 10; $i++) {
            $key = $this->makeKey("bulk_{$i}");
            $keys[] = $key;
            SimpleCache::set($key, "value_{$i}");
        }

        foreach ($keys as $i => $key) {
            $this->assertEquals("value_{$i}", SimpleCache::get($key));
        }

        foreach ($keys as $key) {
            SimpleCache::delete($key);
        }

        foreach ($keys as $key) {
            $this->assertNull(SimpleCache::get($key));
        }
    }
}
