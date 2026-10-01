# Repository guidance

## Theme screenshots

To refresh the full-page homepage and Comet Pulse T-Shirt screenshots for Canvas or Volt, run:

```bash
node scripts/capture-theme-screenshots.mjs canvas
node scripts/capture-theme-screenshots.mjs volt
```

The script saves the screenshots in `docs/images/`. Use `--base-url <url>` to capture from another storefront URL, or `--output-dir <dir>` to choose another output directory. The default storefront is `https://app.test/en_US/`.

The script requires Node.js 22+ and Google Chrome or Chromium. Set `CHROME_BIN` if the browser executable is not detected automatically. The selected theme must be active on the storefront.
