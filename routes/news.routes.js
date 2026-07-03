const express = require("express");
const router = express.Router();
const db = require("../config/db");

// GET all news
router.get("/", async (req, res) => {
  try {
    const [rows] = await db.query(
      "SELECT * FROM news ORDER BY created_at DESC"
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// GET single news by id
router.get("/:id", async (req, res) => {
  try {
    const [rows] = await db.query("SELECT * FROM news WHERE id = ?", [
      req.params.id,
    ]);
    if (rows.length === 0) return res.status(404).json({ error: "Not found" });
    res.json(rows[0]);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// POST create news
router.post("/", async (req, res) => {
  const { title, content, image_name, is_new, link_url, created_at } = req.body;
  try {
    const [result] = await db.query(
      "INSERT INTO news (title, content, image_name, is_new, link_url, created_at) VALUES (?, ?, ?, ?, ?, ?)",
      [title, content, image_name || "default.jpg", is_new ?? 1, link_url, created_at]
    );
    res.status(201).json({ id: result.insertId, message: "Created" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// PUT update news
router.put("/:id", async (req, res) => {
  const { title, content, image_name, is_new, link_url, created_at } = req.body;
  try {
    await db.query(
      "UPDATE news SET title=?, content=?, image_name=?, is_new=?, link_url=?, created_at=? WHERE id=?",
      [title, content, image_name, is_new, link_url, created_at, req.params.id]
    );
    res.json({ message: "Updated" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// DELETE news
router.delete("/:id", async (req, res) => {
  try {
    await db.query("DELETE FROM news WHERE id = ?", [req.params.id]);
    res.json({ message: "Deleted" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
