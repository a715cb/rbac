<?php
/**
 * User-Agent解析服务
 *
 * @类名: UserAgentParserService
 * @功能: 从HTTP User-Agent字符串中提取浏览器和操作系统信息
 * @描述: 解析客户端浏览器的User-Agent，提供浏览器类型和操作系统类型识别
 *
 * @职责:
 *   1. 浏览器类型识别
 *   2. 操作系统类型识别
 *   3. User-Agent信息提取
 *
 * @设计思路:
 *   - 基于正则表达式模式匹配
 *   - 支持主流浏览器和操作系统识别
 *   - 提供未知类型的友好返回
 *
 * @依赖组件: 无
 *
 * @使用示例:
 *   $parser = UserAgentParserService::getInstance();
 *   $os = $parser->parseOs('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124');
 *   $browser = $parser->parseBrowser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124');
 */

namespace app\admin\service;

class UserAgentParserService
{
    private static ?UserAgentParserService $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 解析操作系统类型
     *
     * @方法用途: 从User-Agent字符串中提取操作系统信息
     * @功能描述: 使用正则表达式匹配常见操作系统的特征字符串
     *
     * @参数说明:
     *   - userAgent (string): 浏览器的User-Agent字符串
     *
     * @返回类型: string
     *   - 返回操作系统名称，可能的值：
     *     Windows 10, Windows 8.1, Windows 8, Windows 7
     *     macOS, Linux, iOS, Android, Unknown
     *
     * @匹配规则:
     *   - Windows NT 10.0: Windows 10
     *   - Windows NT 6.3: Windows 8.1
     *   - Windows NT 6.2: Windows 8
     *   - Windows NT 6.1: Windows 7
     *   - Mac OS X: macOS
     *   - Linux: Linux
     *   - iPhone: iOS
     *   - Android: Android
     *
     * @使用场景:
     *   - 登录日志记录操作系统信息
     *   - 安全审计分析用户设备分布
     *   - 支持基于操作系统的访问控制（可选）
     */
    public function parseOs(string $userAgent): string
    {
        if (preg_match('/Windows NT 10/i', $userAgent)) return 'Windows 10';
        if (preg_match('/Windows NT 6.3/i', $userAgent)) return 'Windows 8.1';
        if (preg_match('/Windows NT 6.2/i', $userAgent)) return 'Windows 8';
        if (preg_match('/Windows NT 6.1/i', $userAgent)) return 'Windows 7';
        if (preg_match('/Mac OS X/i', $userAgent)) return 'macOS';
        if (preg_match('/Linux/i', $userAgent)) return 'Linux';
        if (preg_match('/iPhone/i', $userAgent)) return 'iOS';
        if (preg_match('/Android/i', $userAgent)) return 'Android';
        return 'Unknown';
    }

    /**
     * 解析浏览器类型
     *
     * @方法用途: 从User-Agent字符串中提取浏览器信息
     * @功能描述: 使用正则表达式匹配常见浏览器的特征字符串
     *
     * @参数说明:
     *   - userAgent (string): 浏览器的User-Agent字符串
     *
     * @返回类型: string
     *   - 返回浏览器名称，可能的值：
     *     IE, Edge, Firefox, Chrome, Safari, Opera, Unknown
     *
     * @匹配规则:
     *   - MSIE 或 Trident: IE浏览器
     *   - Edge: Microsoft Edge
     *   - Firefox: Mozilla Firefox
     *   - Chrome: Google Chrome
     *   - Safari（不含Chrome）: Safari
     *   - Opera: Opera
     *
     * @特殊说明:
     *   - Chrome和Safari都包含Safari标识，需先排除Chrome
     *   - IE 11使用Trident替代MSIE标识
     *
     * @使用场景:
     *   - 登录日志记录浏览器信息
     *   - 安全审计分析用户浏览器分布
     *   - 兼容性问题排查支持
     */
    public function parseBrowser(string $userAgent): string
    {
        if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) return 'IE';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) return 'Safari';
        if (preg_match('/Opera/i', $userAgent)) return 'Opera';
        return 'Unknown';
    }

    /**
     * 解析完整的User-Agent信息
     *
     * @方法用途: 提取User-Agent的所有相关信息
     * @功能描述: 返回包含操作系统、浏览器和完整UA字符串的结构化数据
     *
     * @参数说明:
     *   - userAgent (string): 浏览器的User-Agent字符串
     *
     * @返回类型: array
     *   - 'os': 操作系统类型
     *   - 'browser': 浏览器类型
     *   - 'user_agent': 原始User-Agent字符串
     */
    public function parse(string $userAgent): array
    {
        return [
            'os' => $this->parseOs($userAgent),
            'browser' => $this->parseBrowser($userAgent),
            'user_agent' => $userAgent,
        ];
    }
}
