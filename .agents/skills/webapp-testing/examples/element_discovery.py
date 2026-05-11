from playwright.sync_api import sync_playwright


def discover_elements(url: str):
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto(url)
        page.wait_for_load_state('networkidle')

        buttons = page.locator('button').all()
        links = page.locator('a').all()
        inputs = page.locator('input, textarea, select').all()

        print(f"Found {len(buttons)} buttons:")
        for i, btn in enumerate(buttons[:20]):
            text = btn.text_content() or ''
            print(f"  [{i}] {text.strip()[:50]}")

        print(f"\nFound {len(links)} links:")
        for i, link in enumerate(links[:20]):
            text = link.text_content() or ''
            href = link.get_attribute('href') or ''
            print(f"  [{i}] {text.strip()[:30]} -> {href[:50]}")

        print(f"\nFound {len(inputs)} inputs:")
        for i, inp in enumerate(inputs[:20]):
            tag = inp.evaluate('el => el.tagName')
            input_type = inp.get_attribute('type') or ''
            name = inp.get_attribute('name') or ''
            placeholder = inp.get_attribute('placeholder') or ''
            print(f"  [{i}] <{tag} type={input_type} name={name} placeholder={placeholder}>")

        browser.close()


if __name__ == "__main__":
    import sys
    url = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:5173"
    discover_elements(url)
