INSERT INTO files (disk_path, original_name, mime_type, size_bytes, alt_text, uploaded_by)
SELECT 'assets/img/hero/cacao1.png', 'cacao1.png', 'image/png', 0, 'Acopio comunitario de cacao', u.id
FROM users u
WHERE u.email = 'admin@ccopaeca.org.pe'
AND NOT EXISTS (SELECT 1 FROM files f WHERE f.disk_path = 'assets/img/hero/cacao1.png');

INSERT INTO files (disk_path, original_name, mime_type, size_bytes, alt_text, uploaded_by)
SELECT 'assets/img/hero/cacao3.png', 'cacao3.png', 'image/png', 0, 'Fermentacion controlada de cacao', u.id
FROM users u
WHERE u.email = 'admin@ccopaeca.org.pe'
AND NOT EXISTS (SELECT 1 FROM files f WHERE f.disk_path = 'assets/img/hero/cacao3.png');

INSERT INTO files (disk_path, original_name, mime_type, size_bytes, alt_text, uploaded_by)
SELECT 'assets/img/hero/aereo.png', 'aereo.png', 'image/png', 0, 'Parcelas productivas de cacao', u.id
FROM users u
WHERE u.email = 'admin@ccopaeca.org.pe'
AND NOT EXISTS (SELECT 1 FROM files f WHERE f.disk_path = 'assets/img/hero/aereo.png');

INSERT INTO posts (author_id, title, slug, excerpt, category, content, featured_image_id, status, published_at, meta_title, meta_description, meta_keywords)
SELECT u.id, 'Acopio Comunitario', 'acopio-comunitario',
       'Familias productoras organizan la entrega de cacao fresco y seco con registros transparentes por lote.',
       'Acopio',
       '<p>El acopio comunitario permite recibir, pesar y clasificar cacao de productores aliados con criterios claros de calidad, trazabilidad y pago justo.</p>',
       (SELECT id FROM files WHERE disk_path = 'assets/img/hero/cacao1.png' LIMIT 1),
       'published', NOW() - INTERVAL 6 DAY,
       'Acopio Comunitario de Cacao', 'Registro, seleccion y trazabilidad en el acopio comunitario de cacao.', 'cacao, acopio, cooperativa'
FROM users u WHERE u.email = 'admin@ccopaeca.org.pe'
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt), category = VALUES(category), content = VALUES(content), featured_image_id = VALUES(featured_image_id), status = VALUES(status);

INSERT INTO posts (author_id, title, slug, excerpt, category, content, featured_image_id, status, published_at, meta_title, meta_description, meta_keywords)
SELECT u.id, 'Fermentacion controlada', 'fermentacion-controlada',
       'Procesos de fermentacion y secado que mejoran aroma, color, humedad y rendimiento comercial.',
       'Calidad',
       '<p>La fermentacion controlada ordena tiempos, temperatura y manejo del grano para lograr perfiles aromaticos mas estables y lotes mejor valorizados.</p>',
       (SELECT id FROM files WHERE disk_path = 'assets/img/hero/cacao3.png' LIMIT 1),
       'published', NOW() - INTERVAL 5 DAY,
       'Fermentacion controlada de cacao', 'Buenas practicas para mejorar fermentacion, secado y calidad del cacao.', 'fermentacion, secado, cacao'
FROM users u WHERE u.email = 'admin@ccopaeca.org.pe'
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt), category = VALUES(category), content = VALUES(content), featured_image_id = VALUES(featured_image_id), status = VALUES(status);

INSERT INTO posts (author_id, title, slug, excerpt, category, content, featured_image_id, status, published_at, meta_title, meta_description, meta_keywords)
SELECT u.id, 'Parcelas productivas', 'parcelas-productivas',
       'Acompanamiento a parcelas aliadas para fortalecer productividad, trazabilidad y buenas practicas.',
       'Comunidad',
       '<p>El seguimiento de parcelas productivas ayuda a ordenar informacion del origen, mejorar cosecha y sostener procesos de produccion responsable.</p>',
       (SELECT id FROM files WHERE disk_path = 'assets/img/hero/aereo.png' LIMIT 1),
       'published', NOW() - INTERVAL 4 DAY,
       'Parcelas productivas de cacao', 'Acompanamiento tecnico para parcelas productivas de cacao.', 'parcelas, productores, cacao'
FROM users u WHERE u.email = 'admin@ccopaeca.org.pe'
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt), category = VALUES(category), content = VALUES(content), featured_image_id = VALUES(featured_image_id), status = VALUES(status);

INSERT INTO posts (author_id, title, slug, excerpt, category, content, featured_image_id, status, published_at, meta_title, meta_description, meta_keywords)
SELECT u.id, 'Nibs y pasta de cacao', 'nibs-y-pasta-de-cacao',
       'Transformacion demostrativa de cacao en nibs, pasta y derivados con mayor valor agregado.',
       'Derivados',
       '<p>Los nibs y la pasta de cacao permiten presentar derivados con identidad de origen, aroma intenso y mayor oportunidad comercial.</p>',
       (SELECT id FROM files WHERE disk_path = 'assets/img/hero/cacao1.png' LIMIT 1),
       'published', NOW() - INTERVAL 3 DAY,
       'Nibs y pasta de cacao', 'Derivados de cacao con valor agregado para compradores especializados.', 'nibs, pasta, derivados'
FROM users u WHERE u.email = 'admin@ccopaeca.org.pe'
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt), category = VALUES(category), content = VALUES(content), featured_image_id = VALUES(featured_image_id), status = VALUES(status);

INSERT INTO posts (author_id, title, slug, excerpt, category, content, featured_image_id, status, published_at, meta_title, meta_description, meta_keywords)
SELECT u.id, 'Capacitacion tecnica', 'capacitacion-tecnica',
       'Sesiones practicas sobre cosecha, seleccion, beneficio, inocuidad y manejo postcosecha.',
       'Capacitacion',
       '<p>La capacitacion tecnica fortalece criterios de cosecha, seleccion, fermentacion, secado e inocuidad para mejorar la calidad final.</p>',
       (SELECT id FROM files WHERE disk_path = 'assets/img/hero/cacao3.png' LIMIT 1),
       'published', NOW() - INTERVAL 2 DAY,
       'Capacitacion tecnica en cacao', 'Capacitacion a productores para mejorar calidad de cacao.', 'capacitacion, productores, calidad'
FROM users u WHERE u.email = 'admin@ccopaeca.org.pe'
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt), category = VALUES(category), content = VALUES(content), featured_image_id = VALUES(featured_image_id), status = VALUES(status);

INSERT INTO posts (author_id, title, slug, excerpt, category, content, featured_image_id, status, published_at, meta_title, meta_description, meta_keywords)
SELECT u.id, 'Calidad de exportacion', 'calidad-de-exportacion',
       'Evaluacion de lotes para compradores que exigen consistencia, origen y documentacion confiable.',
       'Calidad',
       '<p>La calidad de exportacion exige control de humedad, defectos, fermentacion, almacenamiento y documentacion de trazabilidad.</p>',
       (SELECT id FROM files WHERE disk_path = 'assets/img/hero/aereo.png' LIMIT 1),
       'published', NOW() - INTERVAL 1 DAY,
       'Calidad de exportacion en cacao', 'Control de calidad para cacao trazable y exportable.', 'exportacion, trazabilidad, calidad'
FROM users u WHERE u.email = 'admin@ccopaeca.org.pe'
ON DUPLICATE KEY UPDATE excerpt = VALUES(excerpt), category = VALUES(category), content = VALUES(content), featured_image_id = VALUES(featured_image_id), status = VALUES(status);
