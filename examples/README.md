# Synthetic Reproducibility Example

`synthetic_scenarios.csv` contains no real student information. Each row is a
minimal risk-classification scenario using the default thresholds. The
`expected_risklevel` column is the expected output of the risk engine.

Use these scenarios when implementing a Moodle data generator or an external
verification harness. Runtime results must record the exact plugin, Moodle,
PHP, and database versions.

