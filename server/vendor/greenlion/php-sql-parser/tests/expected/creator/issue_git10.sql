SELECT REPLACE(f.web_program,'
','') AS web_program, id AS change_id FROM file f HAVING change_id > :change_id