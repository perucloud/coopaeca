-- Datos ficticios para landing COOPAECA: cacao, derivados y servicios.

INSERT IGNORE INTO categories (type, name, slug, description, position, is_active) VALUES
('product', 'Cacao en grano', 'cacao-en-grano', 'Lotes de cacao seco, fermentado y seleccionado.', 1, 1),
('product', 'Derivados del cacao', 'derivados-del-cacao', 'Productos transformados para chocolateria y agroindustria.', 2, 1);

INSERT INTO products
(sku, name, slug, short_description, description, price, stock, is_featured, status, meta_title, meta_description)
VALUES
('CAC-GR-001', 'Cacao en grano fermentado', 'cacao-en-grano-fermentado',
 'Granos seleccionados, fermentados y secados para compradores especializados.',
 'Cacao seco de origen cooperativo, clasificado por lote, con control de humedad, aroma y trazabilidad desde el productor.',
 0.00, NULL, 1, 'published', 'Cacao en grano fermentado', 'Cacao en grano con trazabilidad y calidad para comercializacion.'),
('CAC-NB-002', 'Nibs de cacao tostado', 'nibs-de-cacao-tostado',
 'Trozos crujientes de cacao con aroma intenso para chocolateria y reposteria.',
 'Nibs obtenidos de granos seleccionados, tostados y triturados para formulaciones saludables y productos de valor agregado.',
 0.00, NULL, 1, 'published', 'Nibs de cacao tostado', 'Nibs de cacao para chocolateria, reposteria y snacks.'),
('CAC-PA-003', 'Pasta de cacao natural', 'pasta-de-cacao-natural',
 'Masa pura de cacao para elaboracion de chocolates, coberturas y bebidas.',
 'Pasta de cacao sin aditivos, ideal para clientes que buscan intensidad, origen y perfil aromatico definido.',
 0.00, NULL, 1, 'published', 'Pasta de cacao natural', 'Pasta de cacao para derivados y chocolateria.'),
('CAC-MT-004', 'Manteca de cacao', 'manteca-de-cacao',
 'Derivado premium para chocolateria fina, cosmetica natural y formulaciones especiales.',
 'Manteca obtenida a partir de cacao seleccionado, orientada a aplicaciones alimentarias y cosmeticas.',
 0.00, NULL, 0, 'published', 'Manteca de cacao', 'Manteca de cacao para chocolateria y cosmetica.'),
('CAC-PV-005', 'Cacao en polvo', 'cacao-en-polvo',
 'Polvo de cacao aromatico para bebidas, panaderia, heladeria y cocina saludable.',
 'Cacao pulverizado de sabor intenso, pensado para uso gastronomico, bebidas calientes y mezclas comerciales.',
 0.00, NULL, 0, 'published', 'Cacao en polvo', 'Cacao en polvo para alimentos y bebidas.'),
('CAC-CH-006', 'Chocolate bitter artesanal', 'chocolate-bitter-artesanal',
 'Tabletas ficticias de alto porcentaje, inspiradas en cacao de origen cooperativo.',
 'Chocolate bitter elaborado como producto demostrativo de valor agregado para presentar el potencial del cacao local.',
 0.00, NULL, 0, 'published', 'Chocolate bitter artesanal', 'Chocolate bitter de origen cooperativo.')
ON DUPLICATE KEY UPDATE
short_description = VALUES(short_description),
description = VALUES(description),
status = VALUES(status),
is_featured = VALUES(is_featured);

INSERT IGNORE INTO product_category (product_id, category_id)
SELECT p.id, c.id
FROM products p
JOIN categories c ON c.slug = CASE
    WHEN p.slug IN ('cacao-en-grano-fermentado') THEN 'cacao-en-grano'
    ELSE 'derivados-del-cacao'
END
WHERE p.slug IN (
    'cacao-en-grano-fermentado',
    'nibs-de-cacao-tostado',
    'pasta-de-cacao-natural',
    'manteca-de-cacao',
    'cacao-en-polvo',
    'chocolate-bitter-artesanal'
);

INSERT INTO services
(name, slug, icon_name, short_description, description, position, is_active, meta_title, meta_description)
VALUES
('Acopio de cacao', 'acopio-de-cacao', 'package',
 'Recepcion, pesaje y clasificacion de cacao fresco y seco de productores aliados.',
 'Servicio de acopio con registro por productor, control de humedad, evaluacion inicial y ordenamiento por lote.',
 1, 1, 'Acopio de cacao', 'Acopio de cacao con trazabilidad.'),
('Fermentacion y secado', 'fermentacion-y-secado', 'activity',
 'Acompanamiento tecnico para mejorar aroma, color, humedad y rendimiento del grano.',
 'Proceso ficticio de beneficio postcosecha que prioriza estandarizacion, calidad sensorial y control del secado.',
 2, 1, 'Fermentacion y secado', 'Proceso postcosecha para cacao de calidad.'),
('Control de calidad', 'control-de-calidad-cacao', 'shield',
 'Evaluacion de humedad, fermentacion, defectos, aroma y trazabilidad por lote.',
 'Control de calidad para separar lotes, reducir riesgos comerciales y preparar productos con especificaciones claras.',
 3, 1, 'Control de calidad de cacao', 'Calidad y trazabilidad del cacao.'),
('Transformacion de derivados', 'transformacion-de-derivados', 'layers',
 'Elaboracion ficticia de nibs, pasta, manteca, polvo y chocolate demostrativo.',
 'Servicio de valor agregado orientado a mostrar el potencial comercial de los derivados del cacao.',
 4, 1, 'Derivados del cacao', 'Transformacion de cacao en derivados.'),
('Comercializacion asociativa', 'comercializacion-asociativa', 'share',
 'Articulacion con compradores y preparacion comercial de cacao y derivados.',
 'Soporte comercial para conectar productores organizados con clientes nacionales e internacionales.',
 5, 1, 'Comercializacion de cacao', 'Venta asociativa de cacao y derivados.'),
('Capacitacion a productores', 'capacitacion-a-productores', 'users',
 'Asistencia ficticia en cosecha, manejo postcosecha, calidad y sostenibilidad.',
 'Capacitaciones para fortalecer capacidades productivas y mejorar la calidad del cacao desde el campo.',
 6, 1, 'Capacitacion cacao', 'Capacitacion tecnica para productores.')
ON DUPLICATE KEY UPDATE
icon_name = VALUES(icon_name),
short_description = VALUES(short_description),
description = VALUES(description),
position = VALUES(position),
is_active = VALUES(is_active);

INSERT INTO social_networks (platform, url, is_active, position) VALUES
('Facebook', 'https://facebook.com/ccopaeca', 1, 1),
('Instagram', 'https://instagram.com/ccopaeca', 1, 2),
('LinkedIn', 'https://linkedin.com/company/ccopaeca', 1, 3)
ON DUPLICATE KEY UPDATE
url = VALUES(url),
is_active = VALUES(is_active),
position = VALUES(position);
