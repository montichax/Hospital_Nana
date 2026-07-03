const express = require("express");
const cors = require("cors");
require("dotenv").config();

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use("/api/news", require("./routes/news.routes"));
app.use("/api/departments", require("./routes/departments.routes"));
app.use("/api/banners", require("./routes/banners.routes"));

app.get("/", (req, res) => {
  res.json({ message: "Pakchong Nana Hospital API", status: "running" });
});

app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});
