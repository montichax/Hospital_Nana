const express = require("express");
const router = express.Router();
const db = require("../config/db");

// GET all departments
router.get("/", async (req, res) => {
  try {
    const [rows] = await db.query("SELECT * FROM departments ORDER BY id");
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// GET department with its contents
router.get("/:id/contents", async (req, res) => {
  try {
    const [dept] = await db.query("SELECT * FROM departments WHERE id = ?", [
      req.params.id,
    ]);
    if (dept.length === 0) return res.status(404).json({ error: "Not found" });

    const [contents] = await db.query(
      "SELECT * FROM department_contents WHERE department_id = ? ORDER BY section, sort_order",
      [req.params.id]
    );

    res.json({ department: dept[0], contents });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// GET contents by section
router.get("/:id/contents/:section", async (req, res) => {
  try {
    const [rows] = await db.query(
      "SELECT * FROM department_contents WHERE department_id = ? AND section = ? ORDER BY sort_order",
      [req.params.id, req.params.section]
    );
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// POST add department content
router.post("/:id/contents", async (req, res) => {
  const { section, title, content, file_name, link_url, sort_order } = req.body;
  try {
    const [result] = await db.query(
      "INSERT INTO department_contents (department_id, section, title, content, file_name, link_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)",
      [req.params.id, section, title, content, file_name, link_url, sort_order || 0]
    );
    res.status(201).json({ id: result.insertId, message: "Created" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// DELETE department content
router.delete("/contents/:contentId", async (req, res) => {
  try {
    await db.query("DELETE FROM department_contents WHERE id = ?", [
      req.params.contentId,
    ]);
    res.json({ message: "Deleted" });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
