import subprocess
import sys
import time
import argparse
import signal
import os
import requests
from typing import List, Optional


def parse_args():
    parser = argparse.ArgumentParser(
        description="Start one or more servers, wait for them to be ready, then run a command."
    )
    parser.add_argument(
        "--server",
        action="append",
        nargs=2,
        metavar=("CMD", "PORT"),
        help="Server command and port (can be specified multiple times)",
    )
    parser.add_argument(
        "--timeout",
        type=int,
        default=60,
        help="Maximum seconds to wait for each server to become ready (default: 60)",
    )
    parser.add_argument(
        "command",
        nargs=argparse.REMAINDER,
        help="Command to run after servers are ready (prefix with --)",
    )
    return parser.parse_args()


def wait_for_server(port: int, timeout: int) -> bool:
    start = time.time()
    while time.time() - start < timeout:
        try:
            resp = requests.get(f"http://localhost:{port}", timeout=2)
            if resp.status_code < 500:
                return True
        except requests.ConnectionError:
            pass
        except requests.Timeout:
            pass
        time.sleep(1)
    return False


def main():
    args = parse_args()

    if not args.server:
        print("Error: At least one --server is required", file=sys.stderr)
        sys.exit(1)

    command = [arg for arg in args.command if arg != "--"]
    if not command:
        print("Error: A command to run is required (use -- before the command)", file=sys.stderr)
        sys.exit(1)

    processes: List[subprocess.Popen] = []

    def cleanup(signum=None, frame=None):
        for p in processes:
            try:
                p.terminate()
                p.wait(timeout=5)
            except subprocess.TimeoutExpired:
                p.kill()
            except Exception:
                pass
        if signum is not None:
            sys.exit(0)

    signal.signal(signal.SIGINT, cleanup)
    signal.signal(signal.SIGTERM, cleanup)

    for cmd_str, port_str in args.server:
        port = int(port_str)
        print(f"Starting server: {cmd_str} (port {port})")
        p = subprocess.Popen(cmd_str, shell=True)
        processes.append(p)

        if wait_for_server(port, args.timeout):
            print(f"Server on port {port} is ready")
        else:
            print(f"Error: Server on port {port} did not become ready within {args.timeout}s", file=sys.stderr)
            cleanup()
            sys.exit(1)

    print("All servers ready. Running command...")
    result = subprocess.run(command)
    cleanup()
    sys.exit(result.returncode)


if __name__ == "__main__":
    main()
