const path = require('path');
const fs = require('fs');

const PROJECT_ROOT = path.resolve(__dirname);

const Config = {
  PROJECT_ROOT,

  REPORT_DIR: path.join(PROJECT_ROOT, 'test-reports'),
  REPORT_HTML_DIR: path.join(PROJECT_ROOT, 'test-reports', 'html'),
  REPORT_JSON_DIR: path.join(PROJECT_ROOT, 'test-reports', 'json'),
  SCREENSHOT_DIR: path.join(PROJECT_ROOT, 'test-screenshots'),
  TEMP_DIR: path.join(PROJECT_ROOT, 'temp'),
  LOG_DIR: path.join(PROJECT_ROOT, 'logs'),

  BASE_URL: 'http://localhost:5173',
  API_URL: 'http://localhost:8000',

  REPORT_PREFIX: 'test_report',
  SCREENSHOT_PREFIX: 'scr',

  ensureDirs() {
    const dirs = [
      this.REPORT_DIR,
      this.REPORT_HTML_DIR,
      this.REPORT_JSON_DIR,
      this.SCREENSHOT_DIR,
      this.TEMP_DIR,
      this.LOG_DIR
    ];
    dirs.forEach(dir => {
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
      }
    });
  },

  getReportFilename(format = 'json', testName = '') {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const prefix = testName ? `${this.REPORT_PREFIX}_${testName}` : this.REPORT_PREFIX;
    return `${prefix}_${timestamp}.${format}`;
  },

  getScreenshotFilename(name) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    return `${this.SCREENSHOT_PREFIX}_${name}_${timestamp}.png`;
  },

  getReportPath(format = 'json', testName = '') {
    const dir = format === 'html' ? this.REPORT_HTML_DIR : this.REPORT_JSON_DIR;
    return path.join(dir, this.getReportFilename(format, testName));
  },

  getScreenshotPath(name) {
    return path.join(this.SCREENSHOT_DIR, this.getScreenshotFilename(name));
  },

  getTempPath(filename) {
    return path.join(this.TEMP_DIR, filename);
  },

  getLogPath(filename) {
    return path.join(this.LOG_DIR, filename || `test_${new Date().toISOString().replace(/[:.]/g, '-')}.log`);
  },

  cleanTempDir() {
    if (fs.existsSync(this.TEMP_DIR)) {
      const files = fs.readdirSync(this.TEMP_DIR);
      for (const file of files) {
        fs.unlinkSync(path.join(this.TEMP_DIR, file));
      }
    }
  }
};

Config.ensureDirs();

module.exports = Config;
