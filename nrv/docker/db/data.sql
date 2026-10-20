USE `nrv_db`;

-- Donnees de demonstration, sans pretendre annoncer la programmation reelle.
INSERT INTO artiste (id_artiste, nom, information, genre) VALUES
    (1, 'Metallica', 'Groupe de heavy metal americain.', 'Heavy metal'),
    (2, 'AC/DC', 'Groupe de hard rock australien.', 'Hard rock'),
    (3, 'Nirvana Tribute', 'Formation hommage au groupe Nirvana.', 'Grunge'),
    (4, 'Foo Fighters', 'Groupe de rock alternatif americain.', 'Rock alternatif'),
    (5, 'Ice Nine Kills', 'Groupe de metalcore americain.', 'Metalcore'),
    (6, 'Fame on Fire', 'Groupe de rock et metalcore americain.', 'Metalcore'),
    (7, 'Linkin Park', 'Groupe de rock alternatif et nu metal.', 'Nu metal')
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom), information = VALUES(information), genre = VALUES(genre);

INSERT INTO soiree (
    id_soiree, nom, thematique, `date`, horaire_debut, lieu, places_disponibles, prix
) VALUES
    (1, 'Legendes du hard rock', 'Heavy metal et hard rock', '2026-10-20', '18:00:00', 'L''Autre Canal, Nancy', 850, 30.00),
    (2, 'Nuit grunge et rock alternatif', 'Grunge et rock alternatif', '2026-10-22', '18:30:00', 'Salle Poirel, Nancy', 420, 30.00),
    (3, 'Metal moderne', 'Metalcore et nu metal', '2026-10-24', '19:00:00', 'Parc de la Pepiniere, Nancy', 1200, 30.00)
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom), thematique = VALUES(thematique), `date` = VALUES(`date`),
    horaire_debut = VALUES(horaire_debut), lieu = VALUES(lieu),
    places_disponibles = VALUES(places_disponibles), prix = VALUES(prix);

INSERT INTO spectacle (
    id_spectacle, id_soiree, titre, `description`, genre, image, video, creneau
) VALUES
    (1, 1, 'Metallica', 'Une soiree de demonstration consacree au heavy metal.', 'Heavy metal', 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a', 'https://www.youtube.com/results?search_query=Metallica+official+live', '19:00:00'),
    (2, 1, 'AC/DC', 'Une selection de hard rock pour la premiere soiree.', 'Hard rock', 'https://images.unsplash.com/photo-1501612780327-45045538702b', 'https://www.youtube.com/results?search_query=ACDC+official+live', '21:15:00'),
    (3, 2, 'Nirvana Tribute', 'Un concert hommage aux classiques du grunge.', 'Grunge', 'https://images.unsplash.com/photo-1506157786151-b8491531f063', 'https://www.youtube.com/results?search_query=Nirvana+official+live', '19:30:00'),
    (4, 2, 'Foo Fighters', 'Rock alternatif pour prolonger la nuit.', 'Rock alternatif', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30', 'https://www.youtube.com/results?search_query=Foo+Fighters+official+live', '21:15:00'),
    (5, 3, 'Ice Nine Kills', 'Metalcore theatrale pour ouvrir la derniere soiree.', 'Metalcore', 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea', 'https://www.youtube.com/results?search_query=Ice+Nine+Kills+official+live', '19:00:00'),
    (6, 3, 'Fame on Fire', 'Rock moderne et metalcore.', 'Metalcore', 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3', 'https://www.youtube.com/results?search_query=Fame+on+Fire+official+live', '20:45:00'),
    (7, 3, 'Linkin Park', 'Un final de demonstration entre rock et nu metal.', 'Nu metal', 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a', 'https://www.youtube.com/results?search_query=Linkin+Park+official+live', '22:15:00')
ON DUPLICATE KEY UPDATE
    id_soiree = VALUES(id_soiree), titre = VALUES(titre), `description` = VALUES(`description`),
    genre = VALUES(genre), image = VALUES(image), video = VALUES(video), creneau = VALUES(creneau);

DELETE FROM spectacle_artiste WHERE id_spectacle BETWEEN 1 AND 7;

INSERT INTO spectacle_artiste (id_spectacle, id_artiste) VALUES
    (1, 1),
    (2, 2),
    (3, 3),
    (4, 4),
    (5, 5),
    (6, 6),
    (7, 7);