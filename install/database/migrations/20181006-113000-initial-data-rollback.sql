DELETE FROM `wordcensor` WHERE (`word_to_censor` = 'shit' and `replace_with` = 's***');
DELETE FROM `wordcensor` WHERE (`word_to_censor` = 'fuck' and `replace_with` = 'f***');
DELETE FROM `spellcheck` WHERE (`missspelling` = 'program o' and `correction` = 'programo');
DELETE FROM `spellcheck` WHERE (`missspelling` = 'program-o' and `correction` = 'programo');
DELETE FROM `spellcheck` WHERE (`missspelling` = 'loool' and `correction` = 'lol');
DELETE FROM `spellcheck` WHERE (`missspelling` = 'lool' and `correction` = 'lol');
DELETE FROM `spellcheck` WHERE (`missspelling` = 'how r u' and `correction` = 'how are you');
DELETE FROM `spellcheck` WHERE (`missspelling` = 'r u ok' and `correction` = 'are you ok');