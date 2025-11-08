// server.js
import express from "express";
import { runCheck } from "./b3-auth.js";

const app = express();
const PORT = process.env.PORT || 10000;

console.log("🚀 Starting b3-auth API Server...");
console.log("Environment:", process.env.RENDER ? "Render" : "Local");

app.get("/", (req, res) => {
  res.send("✅ b3-auth API is live! Use /check?card=CARD|MM|YYYY|CVV");
});

app.get("/check", async (req, res) => {
  const { card, proxy } = req.query;

  if (!card) {
    return res.status(400).json({
      error: 'Missing "card" parameter. Use format: CARD|MM|YYYY|CVV',
    });
  }

  try {
    const result = await runCheck(card, proxy || null);
    res.json(result);
  } catch (err) {
    console.error("❌ API error:", err.message);
    res.status(500).json({
      status: "#Error",
      message: err.message,
    });
  }
});

app.listen(PORT, () => {
  console.log(`✅ Server running on port ${PORT}`);
});
