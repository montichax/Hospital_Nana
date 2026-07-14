const express = require("express");
const router = express.Router();
const db = require("../config/db");

// GET all active site-wide (หน้าแรก) banners
router.get("/", async (req, res) => {
  try {
    const [rows] = await db.query(
      "SELECT * FROM banners WHERE is_active = 1 AND department_id IS NULL ORDER BY sort_order"
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// GET all banners (admin)
router.get("/all", async (req, res) => {
  try {
    const [rows] = await db.query("SELECT * FROM banners ORDER BY sort_order");
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// GET all active banners for a specific department
router.get("/department/:id", async (req, res) => {
  try {
    const [rows] = await db.query(
      "SELECT * FROM banners WHERE is_active = 1 AND department_id = ? ORDER BY sort_order",
      [req.params.id]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// POST create banner
router.post("/", async (req, res) => {
  const { department_id, title, subtitle, image_name, link_url, sort_order, is_active } = req.body;
  try {
    const [result] = await db.query(
      "INSERT INTO banners (department_id, title, subtitle, image_name, link_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
      [department_id || null, title, subtitle, image_name, link_url, sort_order || 1, is_active ?? 1]
    );
    res.status(201).json({ id: result.insertId, message: "Created" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// PUT update banner
router.put("/:id", async (req, res) => {
  const { department_id, title, subtitle, image_name, link_url, sort_order, is_active } = req.body;
  try {
    await db.query(
      "UPDATE banners SET department_id=?, title=?, subtitle=?, image_name=?, link_url=?, sort_order=?, is_active=? WHERE id=?",
      [department_id || null, title, subtitle, image_name, link_url, sort_order, is_active, req.params.id]
    );
    res.json({ message: "Updated" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// DELETE banner
router.delete("/:id", async (req, res) => {
  try {
    await db.query("DELETE FROM banners WHERE id = ?", [req.params.id]);
    res.json({ message: "Deleted" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
