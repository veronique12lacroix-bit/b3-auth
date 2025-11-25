import express from "express";
import cors from "cors";
import { runCheck } from "./b3-auth.js";

const app = express();
const PORT = process.env.PORT || 10000;

app.use(cors());
app.use(express.json());

console.log("🚀 Starting b3-auth API Server...");
console.log("Environment:", process.env.RENDER ? "Render" : "Local");

app.get("/", (req, res) => {
  res.send("✅ b3-auth API is live! Use /check?card=CARD|MM|YYYY|CVV");
});

app.get("/check", async (req, res) => {
  const { card, proxy } = req.query;

  if (!card) {
    return res.status(400).send('Missing "card" parameter. Use: CARD|MM|YYYY|CVV');
  }

  try {
    const message = await runCheck(card, proxy || null);

    // 🚫 No status prefix, return ONLY the message
    return res.send(message.trim());
  } catch (err) {
    console.error("❌ API error:", err.message);

    // Return ONLY the error text
    return res.status(500).send(err.message);
  }
});

app.listen(PORT, () => {
  console.log(`✅ Server running on port ${PORT}`);
});
