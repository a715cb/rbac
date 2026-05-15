<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\admin\service\UserAgentParserService;

/**
 * User-Agent解析服务测试
 *
 * @测试目标: UserAgentParserService
 * @测试内容:
 *   - 操作系统类型解析
 *   - 浏览器类型解析
 *   - 完整User-Agent信息解析
 *
 * @覆盖场景:
 *   - Windows各版本识别
 *   - macOS识别
 *   - Linux识别
 *   - iOS/Android移动端识别
 *   - 主流浏览器识别（Chrome, Firefox, Safari, Edge, IE, Opera）
 *   - 未知User-Agent处理
 */

class UserAgentParserServiceTest extends TestCase
{
    private UserAgentParserService $parser;

    protected function setUp(): void
    {
        $this->parser = new UserAgentParserService();
    }

    /**
     * 测试Windows 10操作系统解析
     */
    public function testParseWindows10(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('Windows 10', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试Windows 8.1操作系统解析
     */
    public function testParseWindows81(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('Windows 8.1', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试Windows 8操作系统解析
     */
    public function testParseWindows8(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('Windows 8', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试Windows 7操作系统解析
     */
    public function testParseWindows7(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('Windows 7', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试macOS操作系统解析
     */
    public function testParseMacOS(): void
    {
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('macOS', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试Linux操作系统解析
     */
    public function testParseLinux(): void
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('Linux', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试iOS操作系统解析
     */
    public function testParseIOS(): void
    {
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1';
        $this->assertEquals('iOS', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试Android操作系统解析
     */
    public function testParseAndroid(): void
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36';
        $this->assertEquals('Android', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试未知操作系统解析
     */
    public function testParseUnknownOs(): void
    {
        $userAgent = 'CustomBot/1.0';
        $this->assertEquals('Unknown', $this->parser->parseOs($userAgent));
    }

    /**
     * 测试Chrome浏览器解析
     */
    public function testParseChrome(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('Chrome', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试Firefox浏览器解析
     */
    public function testParseFirefox(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0';
        $this->assertEquals('Firefox', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试Safari浏览器解析（不含Chrome）
     */
    public function testParseSafari(): void
    {
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15';
        $this->assertEquals('Safari', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试Edge浏览器解析
     */
    public function testParseEdge(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59';
        $this->assertEquals('Edge', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试IE浏览器解析（MSIE标识）
     */
    public function testParseIEWithMSIE(): void
    {
        $userAgent = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)';
        $this->assertEquals('IE', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试IE浏览器解析（Trident标识）
     */
    public function testParseIEWithTrident(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';
        $this->assertEquals('IE', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试Opera浏览器解析
     */
    public function testParseOpera(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 OPR/77.0.4054.172';
        $this->assertEquals('Opera', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试未知浏览器解析
     */
    public function testParseUnknownBrowser(): void
    {
        $userAgent = 'CustomBot/1.0';
        $this->assertEquals('Unknown', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试完整User-Agent信息解析
     */
    public function testParseComplete(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $result = $this->parser->parse($userAgent);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('os', $result);
        $this->assertArrayHasKey('browser', $result);
        $this->assertArrayHasKey('user_agent', $result);
        $this->assertEquals('Windows 10', $result['os']);
        $this->assertEquals('Chrome', $result['browser']);
        $this->assertEquals($userAgent, $result['user_agent']);
    }

    /**
     * 测试空User-Agent字符串处理
     */
    public function testParseEmptyUserAgent(): void
    {
        $result = $this->parser->parse('');
        $this->assertEquals('Unknown', $result['os']);
        $this->assertEquals('Unknown', $result['browser']);
    }

    /**
     * 测试Chrome和Safari区分逻辑
     * 确保包含Chrome的Safari标识不会被误判为Safari
     */
    public function testChromeNotMisidentifiedAsSafari(): void
    {
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals('Chrome', $this->parser->parseBrowser($userAgent));
    }

    /**
     * 测试单例模式
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = UserAgentParserService::getInstance();
        $instance2 = UserAgentParserService::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    /**
     * 测试大小写不敏感匹配
     */
    public function testCaseInsensitiveMatching(): void
    {
        $userAgentLower = 'mozilla/5.0 (windows nt 10.0; win64; x64) applewebkit/537.36 (khtml, like gecko) chrome/91.0.4472.124 safari/537.36';
        $userAgentUpper = 'MOZILLA/5.0 (WINDOWS NT 10.0; WIN64; X64) APPLEWEBKIT/537.36 (KHTML, LIKE GECKO) CHROME/91.0.4472.124 SAFARI/537.36';

        $this->assertEquals('Windows 10', $this->parser->parseOs($userAgentLower));
        $this->assertEquals('Windows 10', $this->parser->parseOs($userAgentUpper));
        $this->assertEquals('Chrome', $this->parser->parseBrowser($userAgentLower));
        $this->assertEquals('Chrome', $this->parser->parseBrowser($userAgentUpper));
    }
}
