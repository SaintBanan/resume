<?php

try {

    $model = new waModel();
    $currencies = ['RUB', 'USD', 'EUR'];
    $other_enums = $model->query('SELECT DISTINCT currency FROM btslider_products WHERE currency NOT IN ("RUB", "USD", "EUR")')->fetchAll();

    // Expand a config file with other app product currencies
    if ($other_enums) {

        foreach ($other_enums as &$enum) {
            $currencies[] = $enum['currency'];
        }

        waUtils::varExportToFile($currencies, wa()->getConfig()->getConfigPath('currencies.php', false, 'btslider'));
    }

    $sql_enums = implode("','", $currencies);
    $model->exec("ALTER TABLE btslider_products MODIFY currency ENUM('$sql_enums') NOT NULL");
}
catch (Exception $e) {}