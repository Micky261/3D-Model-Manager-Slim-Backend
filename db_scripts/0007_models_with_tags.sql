CREATE VIEW models_with_tags AS
SELECT *
FROM models m
     JOIN (SELECT model_id, GROUP_CONCAT(tag SEPARATOR 0x1F) AS tags
           FROM model_tags mt
           GROUP BY mt.model_id) mt
          ON m.id = mt.model_id;
