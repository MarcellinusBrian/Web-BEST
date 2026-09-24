export default async function handler(req, res) {
  // Hanya izinkan method POST
  if (req.method !== "POST") {
    return res.status(405).json({ error: "Method not allowed" });
  }

  const { HAWBNo } = req.body || {};

  if (!HAWBNo) {
    return res.status(400).json({ error: "Nomor HAWB tidak boleh kosong" });
  }

  try {
    // Panggil API Pusat dari Server Vercel (Bebas CORS!)
    const apiResponse = await fetch("http://59.153.83.135/api/best/HAWBStatus", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ HAWBNo }),
    });

    const data = await apiResponse.json();
    return res.status(200).json(data);
  } catch (error) {
    console.error("Error Proxy Vercel:", error);
    return res.status(500).json({ error: "Gagal terhubung ke API HAWB" });
  }
}
