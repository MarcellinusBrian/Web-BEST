export default async function handler(req, res) {
  const origin = req.headers.origin;
  const isAllowed =
    !origin || origin === "https://www.bestranspor.com" || origin === "https://bestranspor.com" || origin.includes("vercel.app") || origin.includes("localhost");

  if (isAllowed) {
    res.setHeader("Access-Control-Allow-Origin", origin || "*");
  } else {
    res.setHeader("Access-Control-Allow-Origin", "https://www.bestranspor.com");
  }

  res.setHeader("Access-Control-Allow-Methods", "POST, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type");

  if (req.method === "OPTIONS") {
    return res.status(200).end();
  }

  if (req.method !== "POST") {
    return res.status(405).json({ error: "Method not allowed. Gunakan POST." });
  }

  let { HAWBNo } = req.body || {};

  if (!HAWBNo) {
    return res.status(400).json({ error: "Nomor HAWB tidak boleh kosong." });
  }

  HAWBNo = String(HAWBNo).trim().toUpperCase();

  if (HAWBNo.length > 15) {
    return res.status(400).json({ error: "Format nomor HAWB terlalu panjang." });
  }

  if (!/^[A-Z0-9\-]+$/.test(HAWBNo)) {
    return res.status(400).json({ error: "Nomor HAWB mengandung karakter tidak valid." });
  }

  try {
    const apiResponse = await fetch("http://59.153.83.135/api/best/HAWBStatus", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36",
      },
      body: JSON.stringify({ HAWBNo }),
    });

    const rawText = await apiResponse.text();
    let trimmed = rawText.trim();

    // Jika API Pusat mengembalikan XML tag ASP.NET (<string>...</string>)
    if (trimmed.startsWith("<")) {
      const xmlMatch = trimmed.match(/<string[^>]*>(.*?)<\/string>/s);
      if (xmlMatch && xmlMatch[1]) {
        trimmed = xmlMatch[1].trim();
      }
    }

    // Attempt Parse JSON
    try {
      const jsonData = JSON.parse(trimmed);
      return res.status(200).json(jsonData);
    } catch {
      // Jika bukan JSON murni, kembalikan respon teksnya
      return res.status(200).send(trimmed);
    }
  } catch (error) {
    console.error("Error Proxy Vercel:", error);
    return res.status(502).json({
      error: "Gagal terhubung ke API HAWB Pusat",
      details: error.message,
    });
  }
}
