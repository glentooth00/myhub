UPDATE trades SET forex = 'Mercantile' WHERE YEAR(`date`) < 2023;

UPDATE trades SET forex = 'Capitec' WHERE YEAR(`date`) >= 2023;