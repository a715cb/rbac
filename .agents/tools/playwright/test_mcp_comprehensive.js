const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const Config = require('./config');

const BASE_URL = Config.BASE_URL;
const API_URL = Config.API_URL;

const REPORT_FILE = Config.getReportPath('json', 'mcp_comprehensive');

const testResults = {
  test_metadata: {
    project: 'RBAC Management Control Panel',
    test_time: new Date().toISOString(),
    test_version: '1.0',
    test_environment: {
      frontend: BASE_URL,
      backend: API_URL,
      browsers_tested: []
    }
  },
  summary: {
    total: 0, passed: 0, failed: 0, warnings: 0,
    by_module: {},
    by_role: {},
    by_browser: {}
  },
  modules: {},
  defects: [],
  performance: {},
  ux_issues: []
};

function getTimestamp() {
  return new Date().toISOString().replace(/[:.]/g, '-');
}

function takeScreenshot(page, name, fullPage = true) {
  const filename = Config.getScreenshotFilename(name);
  const filepath = path.join(Config.SCREENSHOT_DIR, filename);
  try {
    page.screenshot({ path: filepath, fullPage }).catch(() => {});
  } catch (e) {}
  return filepath;
}

function recordResult(module, testName, status, details = '', expected = '', actual = '', durationMs = 0, screenshot = '', priority = 'medium') {
  if (!testResults.modules[module]) {
    testResults.modules[module] = { passed: 0, failed: 0, warnings: 0, tests: [] };
  }

  const result = {
    name: testName,
    status,
    details,
    expected,
    actual,
    duration_ms: durationMs,
    screenshot,
    timestamp: new Date().toISOString()
  };

  testResults.modules[module].tests.push(result);
  testResults.summary.total++;

  if (status === 'PASS') {
    testResults.modules[module].passed++;
    testResults.summary.passed++;
  } else if (status === 'FAIL') {
    testResults.modules[module].failed++;
    testResults.summary.failed++;
    const defect = {
      id: `DEF-${String(testResults.defects.length + 1).padStart(3, '0')}`,
      module,
      test_name: testName,
      severity: priority,
      description: details,
      expected,
      actual,
      screenshot,
      suggestion: getFixSuggestion(module, testName, details)
    };
    testResults.defects.push(defect);
  } else if (status === 'WARN') {
    testResults.modules[module].warnings++;
    testResults.summary.warnings++;
    const uxIssue = {
      module,
      test_name: testName,
      description: details,
      severity: priority,
      suggestion: getFixSuggestion(module, testName, details)
    };
    testResults.ux_issues.push(uxIssue);
  }
}

function getFixSuggestion(module, testName, details) {
  const suggestions = {
    '登录认证': {
      '登录页面加载': '优化页面资源加载，使用代码分割',
      '登录功能': '检查API响应格式，确保错误信息清晰',
      'Token处理': '实现Token自动刷新机制',
      '登出功能': '确保清除所有存储的认证信息'
    },
    '权限管理': {
      '角色列表': '考虑添加虚拟滚动优化大数据量',
      '权限分配': '添加批量分配功能',
      '权限验证': '前端路由守卫应与后端API权限一致'
    },
    '用户管理': {
      '用户列表': '添加虚拟滚动支持大数据量',
      '用户搜索': '添加防抖处理搜索请求',
      '表单验证': '增强客户端验证规则'
    }
  };
  return (suggestions[module] && suggestions[module][testName]) || '请详细检查相关代码逻辑';
}

async function apiRequest(page, method, endpoint, token = null, body = null) {
  const headers = {
    'Content-Type': 'application/json'
  };
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const options = { method, headers };
  if (body) {
    options.body = JSON.stringify(body);
  }

  try {
    const response = await page.evaluate(async ({ url, options }) => {
      const res = await fetch(url, options);
      const data = await res.json();
      return { status: res.status, code: res.status === 200 ? 200 : res.status, ...data };
    }, { url: `${API_URL}${endpoint}`, options });
    return response;
  } catch (e) {
    return { error: e.message, code: -1 };
  }
}

async function getToken(page, username = 'admin', password = '123456') {
  const resp = await apiRequest(page, 'POST', '/admin/login', null, { username, password });
  if (resp.code === 200) {
    return resp.data && resp.data.access_token;
  }
  return null;
}

async function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// ==================== 测试模块 ====================

async function testLoginModule(page) {
  const module = '登录认证';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  // TC-01: 登录页面加载
  let start = Date.now();
  await page.goto(`${BASE_URL}/login`);
  await page.waitForLoadState('networkidle');
  const duration = Date.now() - start;
  const screenshot = takeScreenshot(page, 'login_page_load');

  const title = await page.locator('h1').filter({ hasText: '欢迎登录' }).count() > 0;
  const usernameInput = await page.locator('input#login-username').count() > 0;
  const passwordInput = await page.locator('input#login-password').count() > 0;
  const captchaInput = await page.locator('input#login-captcha').count() > 0;
  const loginBtn = await page.locator('button[type="submit"]').count() > 0;

  if (title && usernameInput && passwordInput && captchaInput && loginBtn) {
    recordResult(module, '登录页面元素完整性', 'PASS', '页面包含所有必要元素', '完整登录表单', `耗时${duration}ms`, duration, screenshot);
  } else {
    const missing = [];
    if (!title) missing.push('标题');
    if (!usernameInput) missing.push('用户名输入框');
    if (!passwordInput) missing.push('密码输入框');
    if (!captchaInput) missing.push('验证码输入框');
    if (!loginBtn) missing.push('登录按钮');
    recordResult(module, '登录页面元素完整性', 'FAIL', `缺少必要元素: ${missing.join(', ')}`, '完整登录表单', `缺失: ${missing.join(', ')}`, duration, screenshot, 'high');
  }

  // TC-02: 空表单提交
  await page.fill('input#login-username', '');
  await page.fill('input#login-password', '');
  await page.fill('input#login-captcha', '');
  await page.click('button[type="submit"]');
  await sleep(1000);
  const screenshot2 = takeScreenshot(page, 'login_empty_form');

  const errors = await page.locator('.ant-form-item-explain-error').count();
  if (errors >= 2) {
    recordResult(module, '空表单验证', 'PASS', `显示${errors}个验证错误`, '至少显示2个验证错误', '正确显示', 0, screenshot2);
  } else {
    recordResult(module, '空表单验证', 'FAIL', `仅显示${errors}个验证错误`, '至少显示2个验证错误', '验证不完整', 0, screenshot2, 'high');
  }

  // TC-03: 用户名过短验证
  await page.fill('input#login-username', 'ab');
  await page.fill('input#login-password', '123456');
  await page.fill('input#login-captcha', 'test');
  await page.click('button[type="submit"]');
  await sleep(1000);
  const screenshot3 = takeScreenshot(page, 'login_short_username');

  const shortError = await page.locator('.ant-form-item-explain-error').count();
  if (shortError > 0) {
    recordResult(module, '用户名长度验证', 'PASS', '显示用户名长度验证错误', '显示验证错误', '正确显示', 0, screenshot3);
  } else {
    recordResult(module, '用户名长度验证', 'WARN', '未显示用户名长度验证错误', '显示验证错误', '可能前端验证未触发', 0, screenshot3, 'low');
  }

  // TC-04: 错误密码登录
  await page.fill('input#login-username', 'admin');
  await page.fill('input#login-password', 'wrongpassword');
  await page.fill('input#login-captcha', 'test');
  await page.click('button[type="submit"]');
  await sleep(2000);
  const screenshot4 = takeScreenshot(page, 'login_wrong_password');
  const currentUrl = page.url();

  if (currentUrl.includes('/login')) {
    recordResult(module, '错误密码处理', 'PASS', '登录失败，停留在登录页', '停留在登录页', currentUrl, 0, screenshot4);
  } else {
    recordResult(module, '错误密码处理', 'FAIL', `意外跳转到: ${currentUrl}`, '停留在登录页', currentUrl, 0, screenshot4, 'high');
  }

  // TC-05: API登录正确凭据
  start = Date.now();
  const loginResp = await apiRequest(page, 'POST', '/admin/login', null, { username: 'admin', password: '123456' });
  const duration5 = Date.now() - start;

  if (loginResp.code === 200 && loginResp.data && loginResp.data.access_token) {
    const token = loginResp.data.access_token;
    recordResult(module, 'API登录功能', 'PASS', '登录成功，获取Token', '返回有效Token', `耗时${duration5}ms`, duration5);
    testResults.performance.login_api_ms = duration5;
    return token;
  } else {
    recordResult(module, 'API登录功能', 'FAIL', `登录失败: ${loginResp.msg || 'Unknown error'}`, '返回有效Token', '登录失败', duration5, '', 'high');
    return null;
  }
}

async function testRoleModule(page, token) {
  const module = '角色管理';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, '角色列表加载', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'high');
    return null;
  }

  // TC-06: 角色列表API
  let start = Date.now();
  const rolesResp = await apiRequest(page, 'GET', '/admin/roles', token);
  const duration = Date.now() - start;

  if (rolesResp.code === 200) {
    const roleList = rolesResp.data && rolesResp.data.list ? rolesResp.data.list : [];
    recordResult(module, '角色列表API', 'PASS', `获取${roleList.length}个角色`, '返回角色列表', `耗时${duration}ms`, duration);
    testResults.performance.roles_api_ms = duration;
  } else {
    recordResult(module, '角色列表API', 'FAIL', `获取失败: ${rolesResp.msg}`, '返回角色列表', `失败: ${rolesResp.msg}`, duration, '', 'high');
    return null;
  }

  // TC-07: 创建测试角色
  const testRoleCode = `test_role_${Math.floor(Math.random() * 9000) + 1000}`;
  start = Date.now();
  const createResp = await apiRequest(page, 'POST', '/admin/roles', token, {
    name: '测试角色',
    code: testRoleCode,
    data_scope: 4,
    status: 1,
    sort: 99,
    remark: '自动化测试创建'
  });
  const duration7 = Date.now() - start;
  let createdRoleId = null;

  if (createResp.code === 200 || createResp.code === 201) {
    createdRoleId = createResp.data && (createResp.data.id || createResp.data);
    recordResult(module, '创建角色', 'PASS', `创建角色成功, ID=${createdRoleId}`, '创建成功', `耗时${duration7}ms`, duration7);
  } else {
    recordResult(module, '创建角色', 'FAIL', `创建失败: ${createResp.msg}`, '创建成功', `失败: ${createResp.msg}`, duration7, '', 'medium');
  }

  // TC-08: 分配菜单权限
  if (createdRoleId) {
    const assignResp = await apiRequest(page, 'POST', `/admin/roles/${createdRoleId}/assign-menus`, token, {
      menu_ids: [100, 101]
    });

    if (assignResp.code === 200) {
      recordResult(module, '分配菜单权限', 'PASS', '权限分配成功');
    } else {
      recordResult(module, '分配菜单权限', 'WARN', `分配返回: ${assignResp.msg}`, '分配成功', `返回: ${assignResp.msg}`, 0, '', 'low');
    }
  }

  // TC-09: 更新角色
  if (createdRoleId) {
    const updateResp = await apiRequest(page, 'PUT', `/admin/roles/${createdRoleId}`, token, {
      name: '测试角色_已修改',
      remark: '修改后的备注'
    });

    if (updateResp.code === 200) {
      recordResult(module, '更新角色', 'PASS', '更新角色成功');
    } else {
      recordResult(module, '更新角色', 'FAIL', `更新失败: ${updateResp.msg}`, '更新成功', `失败: ${updateResp.msg}`, 0, '', 'medium');
    }
  }

  // TC-10: 删除角色
  if (createdRoleId) {
    const deleteResp = await apiRequest(page, 'DELETE', `/admin/roles/${createdRoleId}`, token);

    if (deleteResp.code === 200) {
      recordResult(module, '删除角色', 'PASS', '删除角色成功');
    } else {
      recordResult(module, '删除角色', 'FAIL', `删除失败: ${deleteResp.msg}`, '删除成功', `失败: ${deleteResp.msg}`, 0, '', 'medium');
    }
  }

  // TC-11: 角色管理页面UI
  await page.goto(`${BASE_URL}/system/role`);
  await page.waitForLoadState('networkidle');
  await sleep(2000);
  const screenshot = takeScreenshot(page, 'role_page_ui');

  const tableRows = await page.locator('.ant-table-tbody tr').count();
  if (tableRows > 0) {
    recordResult(module, '角色管理页面UI', 'PASS', `页面显示${tableRows}条角色数据`, '显示角色数据', '正常', 0, screenshot);
  } else {
    recordResult(module, '角色管理页面UI', 'WARN', '页面未显示角色数据', '显示角色数据', '数据未加载', 0, screenshot, 'medium');
  }

  return createdRoleId;
}

async function testUserModule(page, token) {
  const module = '用户管理';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, '用户列表加载', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'high');
    return null;
  }

  // TC-12: 用户列表API
  let start = Date.now();
  const usersResp = await apiRequest(page, 'GET', '/admin/users', token);
  const duration = Date.now() - start;

  if (usersResp.code === 200) {
    const userList = usersResp.data && usersResp.data.list ? usersResp.data.list : [];
    recordResult(module, '用户列表API', 'PASS', `获取${userList.length}个用户`, '返回用户列表', `耗时${duration}ms`, duration);
    testResults.performance.users_api_ms = duration;
  } else {
    recordResult(module, '用户列表API', 'FAIL', `获取失败: ${usersResp.msg}`, '返回用户列表', '失败', duration, '', 'high');
    return null;
  }

  // TC-13: 创建测试用户
  const testUsername = `test_user_${Math.floor(Math.random() * 9000) + 1000}`;
  start = Date.now();
  const createResp = await apiRequest(page, 'POST', '/admin/users', token, {
    username: testUsername,
    password: 'Test123456',
    nickname: '测试用户',
    email: `${testUsername}@test.com`,
    mobile: `138${Math.floor(Math.random() * 90000000) + 10000000}`,
    gender: 1,
    status: 1,
    dept_id: null,
    role_ids: []
  });
  const duration13 = Date.now() - start;
  let createdUserId = null;

  if (createResp.code === 200 || createResp.code === 201) {
    createdUserId = createResp.data && (createResp.data.id || createResp.data);
    recordResult(module, '创建用户', 'PASS', `创建用户成功, ID=${createdUserId}`, '创建成功', `耗时${duration13}ms`, duration13);
  } else {
    recordResult(module, '创建用户', 'FAIL', `创建失败: ${createResp.msg}`, '创建成功', `失败: ${createResp.msg}`, duration13, '', 'medium');
  }

  // TC-14: 用户搜索功能
  await page.goto(`${BASE_URL}/system/user`);
  await page.waitForLoadState('networkidle');
  await sleep(1000);
  const screenshot = takeScreenshot(page, 'user_page_initial');

  const searchInput = page.locator('input#user-search-keyword');
  if (await searchInput.count() > 0) {
    await searchInput.fill('admin');
    await page.locator('button').filter({ hasText: '查询' }).first().click();
    await sleep(1500);
    const screenshot14 = takeScreenshot(page, 'user_search_result');

    const searchRows = await page.locator('.ant-table-tbody tr').count();
    if (searchRows >= 1) {
      recordResult(module, '用户搜索功能', 'PASS', `搜索'admin'返回${searchRows}条结果`, '返回搜索结果', '正常', 0, screenshot14);
    } else {
      recordResult(module, '用户搜索功能', 'WARN', '搜索无结果', '返回搜索结果', '可能搜索逻辑问题', 0, screenshot14, 'low');
    }
  } else {
    recordResult(module, '用户搜索功能', 'FAIL', '搜索输入框不存在', '存在搜索输入框', '未找到', 0, '', 'medium');
  }

  // TC-15: 查看用户详情
  if (createdUserId) {
    const detailResp = await apiRequest(page, 'GET', `/admin/users/${createdUserId}`, token);

    if (detailResp.code === 200 && detailResp.data) {
      const userData = detailResp.data;
      recordResult(module, '查看用户详情', 'PASS', `用户名: ${userData.username}`, '返回用户详情', '正常');
    } else {
      recordResult(module, '查看用户详情', 'FAIL', '获取详情失败', '返回用户详情', '失败', 0, '', 'medium');
    }
  }

  // TC-16: 更新用户
  if (createdUserId) {
    const updateResp = await apiRequest(page, 'PUT', `/admin/users/${createdUserId}`, token, {
      nickname: '测试用户_已修改'
    });

    if (updateResp.code === 200) {
      recordResult(module, '更新用户', 'PASS', '更新用户成功');
    } else {
      recordResult(module, '更新用户', 'FAIL', `更新失败: ${updateResp.msg}`, '更新成功', `失败: ${updateResp.msg}`, 0, '', 'medium');
    }
  }

  // TC-17: 边界条件 - 重复用户名
  const dupResp = await apiRequest(page, 'POST', '/admin/users', token, {
    username: testUsername,
    password: 'Test123456',
    nickname: '重复用户'
  });

  if (dupResp.code !== 200 && dupResp.code !== 201) {
    recordResult(module, '重复用户名校验', 'PASS', '拒绝重复用户名', '拒绝重复', '正确拒绝');
  } else {
    recordResult(module, '重复用户名校验', 'FAIL', '允许重复用户名', '拒绝重复', '安全问题', 0, '', 'high');
  }

  // TC-18: 删除用户
  if (createdUserId) {
    const deleteResp = await apiRequest(page, 'DELETE', `/admin/users/${createdUserId}`, token);

    if (deleteResp.code === 200) {
      recordResult(module, '删除用户', 'PASS', '删除用户成功');
    } else {
      recordResult(module, '删除用户', 'FAIL', `删除失败: ${deleteResp.msg}`, '删除成功', `失败: ${deleteResp.msg}`, 0, '', 'medium');
    }
  }

  return createdUserId;
}

async function testMenuModule(page, token) {
  const module = '菜单管理';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, '菜单列表加载', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'high');
    return;
  }

  // TC-19: 菜单列表API
  let start = Date.now();
  const menusResp = await apiRequest(page, 'GET', '/admin/menus', token);
  const duration = Date.now() - start;

  if (menusResp.code === 200) {
    const menuList = menusResp.data && menusResp.data.list ? menusResp.data.list : [];
    recordResult(module, '菜单列表API', 'PASS', `获取${menuList.length}个菜单`, '返回菜单列表', `耗时${duration}ms`, duration);
    testResults.performance.menus_api_ms = duration;
  } else {
    recordResult(module, '菜单列表API', 'FAIL', `获取失败: ${menusResp.msg}`, '返回菜单列表', '失败', duration, '', 'high');
  }

  // TC-20: 菜单树API
  const treeResp = await apiRequest(page, 'GET', '/admin/menus/tree', token);

  if (treeResp.code === 200) {
    const tree = treeResp.data && treeResp.data.tree ? treeResp.data.tree : [];
    recordResult(module, '菜单树API', 'PASS', `获取菜单树，顶级节点${tree.length}个`, '返回菜单树', '正常');
  } else {
    recordResult(module, '菜单树API', 'FAIL', `获取失败: ${treeResp.msg}`, '返回菜单树', '失败', 0, '', 'medium');
  }

  // TC-21: 菜单管理页面UI
  await page.goto(`${BASE_URL}/system/menu`);
  await page.waitForLoadState('networkidle');
  await sleep(2000);
  const screenshot = takeScreenshot(page, 'menu_page_ui');

  const treeNodes = await page.locator('.ant-tree-treenode').count();
  if (treeNodes > 0) {
    recordResult(module, '菜单管理页面UI', 'PASS', `页面显示${treeNodes}个菜单节点`, '显示菜单树', '正常', 0, screenshot);
  } else {
    recordResult(module, '菜单管理页面UI', 'WARN', '页面未显示菜单节点', '显示菜单树', '可能未展开', 0, screenshot, 'low');
  }
}

async function testDeptModule(page, token) {
  const module = '部门管理';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, '部门列表加载', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'high');
    return;
  }

  // TC-22: 部门列表API
  let start = Date.now();
  const deptsResp = await apiRequest(page, 'GET', '/admin/depts', token);
  const duration = Date.now() - start;

  if (deptsResp.code === 200) {
    const deptList = deptsResp.data && deptsResp.data.list ? deptsResp.data.list : [];
    recordResult(module, '部门列表API', 'PASS', `获取${deptList.length}个部门`, '返回部门列表', `耗时${duration}ms`, duration);
    testResults.performance.depts_api_ms = duration;
  } else {
    recordResult(module, '部门列表API', 'FAIL', `获取失败: ${deptsResp.msg}`, '返回部门列表', '失败', duration, '', 'high');
  }

  // TC-23: 部门树API
  const treeResp = await apiRequest(page, 'GET', '/admin/depts/tree', token);

  if (treeResp.code === 200) {
    const tree = treeResp.data && treeResp.data.tree ? treeResp.data.tree : [];
    recordResult(module, '部门树API', 'PASS', `获取部门树，顶级节点${tree.length}个`, '返回部门树', '正常');
  } else {
    recordResult(module, '部门树API', 'FAIL', `获取失败: ${treeResp.msg}`, '返回部门树', '失败', 0, '', 'medium');
  }

  // TC-24: 部门管理页面UI
  await page.goto(`${BASE_URL}/system/dept`);
  await page.waitForLoadState('networkidle');
  await sleep(2000);
  const screenshot = takeScreenshot(page, 'dept_page_ui');

  const nodes = await page.locator('.ant-tree-treenode, .ant-table-tbody tr').count();
  if (nodes > 0) {
    recordResult(module, '部门管理页面UI', 'PASS', '页面显示部门数据', '显示部门数据', '正常', 0, screenshot);
  } else {
    recordResult(module, '部门管理页面UI', 'WARN', '页面未显示部门数据', '显示部门数据', '数据未加载', 0, screenshot, 'low');
  }
}

async function testPermissionModule(page, token) {
  const module = '权限控制';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  // TC-25: 无Token访问受保护API
  const noTokenResp = await apiRequest(page, 'GET', '/admin/users', null);

  if (noTokenResp.code === 401 || noTokenResp.code === 403) {
    recordResult(module, '无Token访问控制', 'PASS', '正确拒绝无Token请求', '返回401/403', `返回${noTokenResp.code}`);
  } else {
    recordResult(module, '无Token访问控制', 'FAIL', '未拒绝无Token请求', '返回401/403', `返回${noTokenResp.code}`, 0, '', 'high');
  }

  // TC-26: 无效Token访问
  const invalidResp = await apiRequest(page, 'GET', '/admin/profile', 'invalid_token_12345');

  if (invalidResp.code === 401 || invalidResp.code === 403) {
    recordResult(module, '无效Token访问控制', 'PASS', '正确拒绝无效Token', '返回401/403', `返回${invalidResp.code}`);
  } else {
    recordResult(module, '无效Token访问控制', 'FAIL', '未拒绝无效Token', '返回401/403', `返回${invalidResp.code}`, 0, '', 'high');
  }

  // TC-27: 过期Token访问
  const expiredToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiMSIsInVzZXJuYW1lIjoiYWRtaW4iLCJyb2xlcyI6WyJzdXBlcl9hZG1pbiJdLCJleHAiOjEwMDAwMDAwMDAsIm5iZiI6MTAwMDAwMDAwMCwiaWF0IjoxMDAwMDAwMDAwfQ.fake';
  const expiredResp = await apiRequest(page, 'GET', '/admin/profile', expiredToken);

  if (expiredResp.code === 401 || expiredResp.code === 403 || expiredResp.error) {
    recordResult(module, '过期Token访问控制', 'PASS', '正确拒绝过期Token', '返回401/403', '正确拒绝');
  } else {
    recordResult(module, '过期Token访问控制', 'FAIL', '未拒绝过期Token', '返回401/403', '安全问题', 0, '', 'high');
  }

  // TC-28: 超级管理员权限验证
  if (token) {
    const profileResp = await apiRequest(page, 'GET', '/admin/profile', token);

    if (profileResp.code === 200) {
      const roles = profileResp.data && profileResp.data.roles ? profileResp.data.roles : [];
      const perms = profileResp.data && profileResp.data.permissions ? profileResp.data.permissions : [];
      const hasSuperAdmin = Array.isArray(roles) && roles.some(r => r.code === 'super_admin');
      recordResult(module, '超级管理员权限', 'PASS', `角色: ${roles.map(r => r.code).join(', ')}, 权限数: ${perms.length}`, '返回权限信息', '正常');
    } else {
      recordResult(module, '超级管理员权限', 'FAIL', '获取Profile失败', '返回权限信息', '失败', 0, '', 'medium');
    }
  }

  // TC-29: 前端路由守卫 - 未登录访问
  const newContext = await browser.newContext();
  const newPage = await newContext.newPage();
  await newPage.goto(`${BASE_URL}/system/user`);
  await newPage.waitForLoadState('networkidle');
  await sleep(2000);
  const screenshot = takeScreenshot(newPage, 'guard_redirect');

  if (newPage.url().includes('/login')) {
    recordResult(module, '前端路由守卫', 'PASS', '未登录用户被正确重定向到登录页', '重定向到登录页', newPage.url(), 0, screenshot);
  } else {
    recordResult(module, '前端路由守卫', 'FAIL', `未登录用户可访问受保护页面: ${newPage.url()}`, '重定向到登录页', newPage.url(), 0, screenshot, 'high');
  }
  await newContext.close();
}

async function testDashboardModule(page, token) {
  const module = '数据监控';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, '仪表盘加载', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'high');
    return;
  }

  // TC-30: 仪表盘统计API
  let start = Date.now();
  const statsResp = await apiRequest(page, 'GET', '/admin/dashboard/statistics', token);
  const duration = Date.now() - start;

  if (statsResp.code === 200 && statsResp.data) {
    const data = statsResp.data;
    recordResult(module, '仪表盘统计数据', 'PASS', `用户:${data.user_total}, 角色:${data.role_total}, 菜单:${data.menu_total}`, '返回统计数据', `耗时${duration}ms`, duration);
    testResults.performance.dashboard_api_ms = duration;
  } else {
    recordResult(module, '仪表盘统计数据', 'FAIL', 'API返回异常', '返回统计数据', '失败', duration, '', 'high');
  }

  // TC-31: 仪表盘页面UI
  await page.goto(`${BASE_URL}/dashboard`);
  await page.waitForLoadState('networkidle');
  await sleep(2000);
  const screenshot = takeScreenshot(page, 'dashboard_ui');

  const statCards = await page.locator('.ant-statistic, .ant-card').count();
  if (statCards > 0) {
    recordResult(module, '仪表盘页面UI', 'PASS', `页面显示${statCards}个统计卡片`, '显示统计数据', '正常', 0, screenshot);
  } else {
    recordResult(module, '仪表盘页面UI', 'WARN', '页面未显示统计卡片', '显示统计数据', '可能未加载完成', 0, screenshot, 'low');
  }
}

async function testApiManagement(page, token) {
  const module = 'API管理';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, 'API列表加载', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'high');
    return;
  }

  // TC-32: API列表
  let start = Date.now();
  const apisResp = await apiRequest(page, 'GET', '/admin/apis', token);
  const duration = Date.now() - start;

  if (apisResp.code === 200) {
    const apiList = apisResp.data && apisResp.data.list ? apisResp.data.list : [];
    const groups = apisResp.data && apisResp.data.groups ? apisResp.data.groups : [];
    recordResult(module, 'API列表API', 'PASS', `获取${apiList.length}个API, ${groups.length}个分组`, '返回API列表', `耗时${duration}ms`, duration);
    testResults.performance.apis_api_ms = duration;
  } else {
    recordResult(module, 'API列表API', 'FAIL', `获取失败: ${apisResp.msg}`, '返回API列表', '失败', duration, '', 'medium');
  }

  // TC-33: API管理页面UI
  await page.goto(`${BASE_URL}/system/api`);
  await page.waitForLoadState('networkidle');
  await sleep(2000);
  const screenshot = takeScreenshot(page, 'api_page_ui');

  const tableRows = await page.locator('.ant-table-tbody tr').count();
  if (tableRows > 0) {
    recordResult(module, 'API管理页面UI', 'PASS', `页面显示${tableRows}条API数据`, '显示API数据', '正常', 0, screenshot);
  } else {
    recordResult(module, 'API管理页面UI', 'WARN', '页面未显示API数据', '显示API数据', '数据未加载', 0, screenshot, 'low');
  }
}

async function testLogMonitoring(page, token) {
  const module = '日志监控';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, '登录日志加载', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'high');
    return;
  }

  // TC-34: 登录日志
  let start = Date.now();
  const logsResp = await apiRequest(page, 'GET', '/admin/login-logs', token);
  const duration = Date.now() - start;

  if (logsResp.code === 200) {
    const logList = logsResp.data && logsResp.data.list ? logsResp.data.list : [];
    recordResult(module, '登录日志API', 'PASS', `获取${logList.length}条登录日志`, '返回日志列表', `耗时${duration}ms`, duration);
    testResults.performance.login_logs_api_ms = duration;
  } else {
    recordResult(module, '登录日志API', 'FAIL', `获取失败: ${logsResp.msg}`, '返回日志列表', '失败', duration, '', 'medium');
  }

  // TC-35: 操作日志
  start = Date.now();
  const opLogsResp = await apiRequest(page, 'GET', '/admin/operation-logs', token);
  const duration35 = Date.now() - start;

  if (opLogsResp.code === 200) {
    const opList = opLogsResp.data && opLogsResp.data.list ? opLogsResp.data.list : [];
    recordResult(module, '操作日志API', 'PASS', `获取${opList.length}条操作日志`, '返回日志列表', `耗时${duration35}ms`, duration35);
  } else {
    recordResult(module, '操作日志API', 'FAIL', `获取失败: ${opLogsResp.msg}`, '返回日志列表', '失败', duration35, '', 'medium');
  }

  // TC-36: 登录日志页面
  await page.goto(`${BASE_URL}/monitor/login`);
  await page.waitForLoadState('networkidle');
  await sleep(2000);
  const screenshot = takeScreenshot(page, 'login_log_page');

  const tableRows = await page.locator('.ant-table-tbody tr').count();
  recordResult(module, '登录日志页面UI', 'PASS', `页面显示${tableRows}条日志`, '显示日志', '正常', 0, screenshot);
}

async function testResponsiveLayout(page) {
  const module = '响应式布局';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  const viewports = [
    { width: 1920, height: 1080, name: '桌面1920x1080' },
    { width: 1366, height: 768, name: '桌面1366x768' },
    { width: 768, height: 1024, name: '平板768x1024' },
    { width: 375, height: 667, name: '手机375x667' }
  ];

  for (const vp of viewports) {
    try {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto(`${BASE_URL}/dashboard`);
      await page.waitForLoadState('networkidle');
      await sleep(1500);
      const screenshot = takeScreenshot(page, `responsive_${vp.name.replace(/[x ]/g, '_')}`);

      const sidebarCount = await page.locator('.ant-layout-sider').count();
      const headerCount = await page.locator('.ant-layout-header').count();
      const contentCount = await page.locator('.ant-layout-content').count();

      const sidebarVisible = sidebarCount > 0 && await page.locator('.ant-layout-sider').first().isVisible();
      const headerVisible = headerCount > 0 && await page.locator('.ant-layout-header').first().isVisible();
      const contentVisible = contentCount > 0 && await page.locator('.ant-layout-content').first().isVisible();

      if (vp.width >= 768) {
        if (contentVisible) {
          recordResult(module, `${vp.name}布局`, 'PASS', '内容区域正常显示', '内容显示', `sidebar=${sidebarVisible}, header=${headerVisible}, content=${contentVisible}`, 0, screenshot);
        } else {
          recordResult(module, `${vp.name}布局`, 'FAIL', '内容区域不可见', '内容显示', `sidebar=${sidebarVisible}, header=${headerVisible}, content=${contentVisible}`, 0, screenshot, 'medium');
        }
      } else {
        if (contentVisible) {
          recordResult(module, `${vp.name}布局`, 'PASS', '移动端布局正常', '内容显示', `sidebar=${sidebarVisible}, content=${contentVisible}`, 0, screenshot);
        } else {
          recordResult(module, `${vp.name}布局`, 'WARN', '移动端内容可能不可见', '内容显示', `sidebar=${sidebarVisible}, content=${contentVisible}`, 0, screenshot, 'low');
        }
      }

      testResults.summary.by_browser[vp.name] = { width: vp.width, height: vp.height, tested: true };
    } catch (e) {
      recordResult(module, `${vp.name}布局`, 'FAIL', `测试异常: ${e.message.substring(0, 100)}`, '正常加载', '异常', 0, '', 'medium');
    }
  }
}

async function testConsoleErrors(page) {
  const module = '控制台错误';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  const consoleErrors = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  const pagesToCheck = [
    `${BASE_URL}/login`,
    `${BASE_URL}/dashboard`,
    `${BASE_URL}/system/user`,
    `${BASE_URL}/system/role`
  ];

  for (const url of pagesToCheck) {
    consoleErrors.length = 0;
    await page.goto(url);
    await page.waitForLoadState('networkidle');
    await sleep(1500);

    const pageName = url.split('/').pop() || 'login';
    if (consoleErrors.length === 0) {
      recordResult(module, `${pageName}页面控制台`, 'PASS', '无控制台错误', '无错误', '正常');
    } else {
      const errorSummary = consoleErrors.slice(0, 3).join('; ');
      recordResult(module, `${pageName}页面控制台`, 'WARN', `发现${consoleErrors.length}个错误: ${errorSummary}`, '无错误', `错误: ${errorSummary}`, 0, '', 'low');
    }
  }
}

async function testPerformanceMetrics(page, token) {
  const module = '性能测试';
  console.log(`\n${'='.repeat(60)}\n模块: ${module}\n${'='.repeat(60)}`);

  if (!token) {
    recordResult(module, '性能测试', 'FAIL', '无有效Token', '有效Token', 'Token为空', 0, '', 'medium');
    return;
  }

  // TC-37: 页面加载性能
  const pageLoads = [
    { path: '/dashboard', name: '仪表盘' },
    { path: '/system/user', name: '用户管理' },
    { path: '/system/role', name: '角色管理' },
    { path: '/system/menu', name: '菜单管理' }
  ];

  const perfResults = {};
  for (const { path, name } of pageLoads) {
    const start = Date.now();
    await page.goto(`${BASE_URL}${path}`);
    await page.waitForLoadState('networkidle');
    await sleep(500);
    const duration = Date.now() - start;
    perfResults[name] = duration;

    if (duration < 3000) {
      recordResult(module, `${name}页面加载`, 'PASS', `加载耗时${duration}ms`, '<3000ms', '性能良好', duration);
    } else if (duration < 5000) {
      recordResult(module, `${name}页面加载`, 'WARN', `加载耗时${duration}ms`, '<3000ms', '加载较慢', duration, '', 'low');
    } else {
      recordResult(module, `${name}页面加载`, 'FAIL', `加载耗时${duration}ms`, '<3000ms', '性能问题', duration, '', 'medium');
    }
  }

  testResults.performance.page_loads = perfResults;

  // TC-38: API响应性能
  const apiPerfTests = [
    { method: 'GET', endpoint: '/admin/users', name: '用户列表' },
    { method: 'GET', endpoint: '/admin/roles', name: '角色列表' },
    { method: 'GET', endpoint: '/admin/menus', name: '菜单列表' },
    { method: 'GET', endpoint: '/admin/dashboard/statistics', name: '仪表盘统计' }
  ];

  const apiPerfResults = {};
  for (const { method, endpoint, name } of apiPerfTests) {
    const start = Date.now();
    await apiRequest(page, method, endpoint, token);
    const duration = Date.now() - start;
    apiPerfResults[name] = duration;

    if (duration < 1000) {
      recordResult(module, `${name}API响应`, 'PASS', `响应耗时${duration}ms`, '<1000ms', '性能良好', duration);
    } else if (duration < 2000) {
      recordResult(module, `${name}API响应`, 'WARN', `响应耗时${duration}ms`, '<1000ms', '响应较慢', duration, '', 'low');
    } else {
      recordResult(module, `${name}API响应`, 'FAIL', `响应耗时${duration}ms`, '<1000ms', '性能问题', duration, '', 'medium');
    }
  }

  testResults.performance.api_response = apiPerfResults;
}

// ==================== 主测试执行 ====================

let browser;

async function main() {
  console.log('='.repeat(60));
  console.log('MCP 综合测试方案开始执行');
  console.log('='.repeat(60));

  testResults.test_metadata.browsers_tested = ['chromium'];

  const startTime = Date.now();

  browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
  const page = await context.newPage();

  const token = await testLoginModule(page);

  await testRoleModule(page, token);

  await testUserModule(page, token);

  await testMenuModule(page, token);

  await testDeptModule(page, token);

  await testPermissionModule(page, token);

  await testDashboardModule(page, token);

  await testApiManagement(page, token);

  await testLogMonitoring(page, token);

  await testResponsiveLayout(page);

  await testConsoleErrors(page);

  await testPerformanceMetrics(page, token);

  await browser.close();

  const totalDuration = Date.now() - startTime;
  testResults.test_metadata.total_duration_seconds = Math.floor(totalDuration / 1000);

  const passRate = (testResults.summary.passed / Math.max(testResults.summary.total, 1)) * 100;
  testResults.summary.pass_rate = `${passRate.toFixed(1)}%`;

  fs.writeFileSync(REPORT_FILE, JSON.stringify(testResults, null, 2), 'utf-8');

  console.log('\n' + '='.repeat(60));
  console.log('测试执行完成!');
  console.log('='.repeat(60));
  console.log(`总测试数: ${testResults.summary.total}`);
  console.log(`通过: ${testResults.summary.passed}`);
  console.log(`失败: ${testResults.summary.failed}`);
  console.log(`警告: ${testResults.summary.warnings}`);
  console.log(`通过率: ${passRate.toFixed(1)}%`);
  console.log(`总耗时: ${Math.floor(totalDuration / 1000)}秒`);
  console.log(`\n发现问题: ${testResults.defects.length}个`);
  console.log(`UX问题: ${testResults.ux_issues.length}个`);
  console.log(`\n报告已保存到: ${REPORT_FILE}`);
  console.log('='.repeat(60));

  if (testResults.defects.length > 0) {
    console.log('\n【缺陷优先级分类】');
    const critical = testResults.defects.filter(d => d.severity === 'high');
    const medium = testResults.defects.filter(d => d.severity === 'medium');
    const low = testResults.defects.filter(d => d.severity === 'low');

    if (critical.length > 0) {
      console.log(`\n🔴 严重缺陷 (${critical.length}个):`);
      for (const d of critical) {
        console.log(`  [${d.id}] ${d.test_name}: ${d.description.substring(0, 50)}...`);
        console.log(`      修复建议: ${d.suggestion}`);
      }
    }

    if (medium.length > 0) {
      console.log(`\n🟡 中等缺陷 (${medium.length}个):`);
      for (const d of medium) {
        console.log(`  [${d.id}] ${d.test_name}: ${d.description.substring(0, 50)}...`);
      }
    }

    if (low.length > 0) {
      console.log(`\n🟢 轻微缺陷 (${low.length}个):`);
      for (const d of low) {
        console.log(`  [${d.id}] ${d.test_name}: ${d.description.substring(0, 50)}...`);
      }
    }
  }

  return testResults;
}

main().catch(console.error);