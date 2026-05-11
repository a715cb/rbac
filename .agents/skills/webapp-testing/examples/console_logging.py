from playwright.sync_api import sync_playwright
import json
import sys


def capture_console_logs(url: str, output_file: str = "/tmp/console_logs.json"):
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        logs = []

        page.on("console", lambda msg: logs.append({
            "type": msg.type,
            "text": msg.text,
            "location": msg.location
        }))

        page.on("pageerror", lambda err: logs.append({
            "type": "error",
            "text": str(err),
        }))

        page.goto(url)
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(3000)

        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(logs, f, indent=2, ensure_ascii=False)

        print(f"Captured {len(logs)} console messages")
        print(f"Saved to: {output_file}")

        errors = [l for l in logs if l['type'] in ('error', 'warning')]
        if errors:
            print(f"\nFound {len(errors)} errors/warnings:")
            for err in errors[:10]:
                print(f"  [{err['type']}] {err['text'][:100]}")

        browser.close()


if __name__ == "__main__":
    url = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:5173"
    output = sys.argv[2] if len(sys.argv) > 2 else "/tmp/console_logs.json"
    capture_console_logs(url, output)
