from playwright.sync_api import sync_playwright
import os


def test_static_html(file_path: str):
    abs_path = os.path.abspath(file_path)
    file_url = f"file:///{abs_path.replace(os.sep, '/')}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto(file_url)

        page.screenshot(path="/tmp/static_page.png", full_page=True)
        print(f"Page title: {page.title()}")
        print(f"Content length: {len(page.content())} chars")

        buttons = page.locator('button').all()
        print(f"Found {len(buttons)} buttons")

        for btn in buttons:
            if btn.is_visible():
                print(f"  Clicking: {btn.text_content()}")
                btn.click()
                page.wait_for_timeout(500)

        browser.close()
        print("Static HTML test completed")


if __name__ == "__main__":
    import sys
    file_path = sys.argv[1] if len(sys.argv) > 1 else "index.html"
    test_static_html(file_path)
