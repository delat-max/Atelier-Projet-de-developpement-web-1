USE `nrv_db`;

START TRANSACTION;


DELETE FROM spectacle_artiste;
DELETE FROM spectacle;
DELETE FROM soiree;
DELETE FROM artiste;


INSERT INTO artiste (id_artiste, nom, information, genre) VALUES
(1, 'Metallica', 'Groupe americain de metal forme en 1981.', 'Heavy metal'),
(2, 'AC/DC', 'Groupe australien legendaire de hard rock.', 'Hard rock'),
(3, 'Nirvana Tribute', 'Formation hommage au groupe Nirvana.', 'Grunge'),
(4, 'Foo Fighters', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(5, 'Ice Nine Kills', 'Groupe americain de metalcore theatrale.', 'Metalcore'),
(6, 'Fame on Fire', 'Groupe americain de rock et metalcore.', 'Metalcore'),
(7, 'Linkin Park', 'Groupe de rock alternatif et de nu metal.', 'Nu metal'),
(8, 'Bon Jovi', 'Groupe americain de hard rock et rock arena.', 'Hard rock'),
(9, 'Les Fatals Picards', 'Groupe francais melant rock et humour.', 'Rock francais'),
(10, 'Falling in Reverse', 'Groupe americain de rock et metalcore.', 'Metalcore'),
(11, 'Green Day', 'Groupe americain de punk rock.', 'Punk rock'),
(12, 'Sum 41', 'Groupe canadien de punk rock.', 'Punk rock'),
(13, 'The Offspring', 'Groupe americain de punk rock.', 'Punk rock'),
(14, 'Blink-182', 'Groupe americain de pop punk.', 'Pop punk'),
(15, 'Muse', 'Groupe britannique de rock alternatif.', 'Rock alternatif'),
(16, 'Queen Tribute', 'Formation hommage au groupe Queen.', 'Rock classique'),
(17, 'Guns N'' Roses', 'Groupe americain de hard rock.', 'Hard rock'),
(18, 'Scorpions', 'Groupe allemand de hard rock.', 'Hard rock'),
(19, 'Iron Maiden', 'Groupe britannique de heavy metal.', 'Heavy metal'),
(20, 'Judas Priest', 'Groupe britannique de heavy metal.', 'Heavy metal'),
(21, 'Rammstein Tribute', 'Formation hommage au groupe Rammstein.', 'Metal industriel'),
(22, 'System of a Down Tribute', 'Formation hommage au groupe System of a Down.', 'Nu metal'),
(23, 'Bring Me the Horizon', 'Groupe britannique de rock et metal alternatif.', 'Metal alternatif'),
(24, 'Architects', 'Groupe britannique de metalcore.', 'Metalcore'),
(25, 'Bad Omens', 'Groupe americain de metal alternatif.', 'Metal alternatif'),
(26, 'Motionless in White', 'Groupe americain de metalcore.', 'Metalcore'),
(27, 'Papa Roach', 'Groupe americain de rock et nu metal.', 'Nu metal'),
(28, 'Korn Tribute', 'Formation hommage au groupe Korn.', 'Nu metal'),
(29, 'Slipknot Tribute', 'Formation hommage au groupe Slipknot.', 'Metal alternatif'),
(30, 'Evanescence', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(31, 'Paramore', 'Groupe americain de rock et pop punk.', 'Pop punk'),
(32, 'Thirty Seconds to Mars', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(33, 'The Killers', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(34, 'Royal Blood', 'Duo britannique de rock.', 'Rock alternatif'),
(35, 'The Pretty Reckless', 'Groupe americain de hard rock.', 'Hard rock'),
(36, 'Airbourne', 'Groupe australien de hard rock.', 'Hard rock'),
(37, 'Europe', 'Groupe suedois de hard rock.', 'Hard rock'),
(38, 'Whitesnake', 'Groupe britannique de hard rock.', 'Hard rock'),
(39, 'Deep Purple Tribute', 'Formation hommage au groupe Deep Purple.', 'Hard rock'),
(40, 'The Rolling Stones Tribute', 'Formation hommage aux Rolling Stones.', 'Rock classique'),
(41, 'The Beatles Tribute', 'Formation hommage aux Beatles.', 'Rock classique'),
(42, 'Telephone Tribute', 'Formation hommage au groupe Telephone.', 'Rock francais'),
(43, 'Indochine', 'Groupe francais de rock et new wave.', 'Rock francais'),
(44, 'Skip The Use', 'Groupe francais de rock alternatif.', 'Rock alternatif'),
(45, 'Shaka Ponk Tribute', 'Formation hommage au groupe Shaka Ponk.', 'Rock alternatif'),
(46, 'Mass Hysteria', 'Groupe francais de metal industriel.', 'Metal industriel'),
(47, 'Gojira', 'Groupe francais de metal.', 'Metal progressif'),
(48, 'Ultra Vomit', 'Groupe francais de metal humoristique.', 'Metal'),
(49, 'Tagada Jones', 'Groupe francais de punk metal.', 'Punk metal'),
(50, 'Trust Tribute', 'Formation hommage au groupe Trust.', 'Hard rock francais'),
(51, 'Rise Against', 'Groupe americain de punk rock.', 'Punk rock'),
(52, 'My Chemical Romance', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(53, 'Fall Out Boy', 'Groupe americain de pop punk.', 'Pop punk'),
(54, 'Weezer', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(55, 'Placebo', 'Groupe de rock alternatif britanno-luxembourgeois.', 'Rock alternatif'),
(56, 'Radiohead Tribute', 'Formation hommage au groupe Radiohead.', 'Rock alternatif'),
(57, 'The Cranberries Tribute', 'Formation hommage au groupe The Cranberries.', 'Rock alternatif'),
(58, 'Seether', 'Groupe sud-africain de rock alternatif.', 'Rock alternatif'),
(59, 'Three Days Grace', 'Groupe canadien de rock alternatif.', 'Rock alternatif'),
(60, 'Breaking Benjamin', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(61, 'Skillet', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(62, 'Halestorm', 'Groupe americain de hard rock.', 'Hard rock'),
(63, 'Volbeat', 'Groupe danois de heavy metal et rockabilly.', 'Heavy metal'),
(64, 'Five Finger Death Punch', 'Groupe americain de heavy metal.', 'Heavy metal'),
(65, 'Avenged Sevenfold', 'Groupe americain de heavy metal.', 'Heavy metal'),
(66, 'Disturbed', 'Groupe americain de heavy metal.', 'Heavy metal'),
(67, 'Nightwish', 'Groupe finlandais de metal symphonique.', 'Metal symphonique'),
(68, 'Epica', 'Groupe neerlandais de metal symphonique.', 'Metal symphonique'),
(69, 'Within Temptation', 'Groupe neerlandais de metal symphonique.', 'Metal symphonique'),
(70, 'Lordi', 'Groupe finlandais de hard rock.', 'Hard rock'),
(71, 'Ghost', 'Groupe suedois de rock et metal.', 'Rock'),
(72, 'The Hives', 'Groupe suedois de garage rock.', 'Garage rock'),
(73, 'Franz Ferdinand', 'Groupe ecossais de rock independant.', 'Rock independant'),
(74, 'Arctic Monkeys', 'Groupe britannique de rock independant.', 'Rock independant'),
(75, 'The Strokes', 'Groupe americain de rock independant.', 'Rock independant'),
(76, 'Pixies Tribute', 'Formation hommage au groupe Pixies.', 'Rock alternatif'),
(77, 'The White Stripes Tribute', 'Formation hommage au duo The White Stripes.', 'Garage rock'),
(78, 'Royal Republic', 'Groupe suedois de rock.', 'Rock'),
(79, 'Nothing More', 'Groupe americain de rock alternatif.', 'Rock alternatif'),
(80, 'Sleeping with Sirens', 'Groupe americain de post-hardcore.', 'Post-hardcore'),
(81, 'Pierce the Veil', 'Groupe americain de post-hardcore.', 'Post-hardcore'),
(82, 'A Day to Remember', 'Groupe americain de pop punk et metalcore.', 'Pop punk');


INSERT INTO soiree (
    id_soiree, nom, thematique, `date`, horaire_debut,
    lieu, places_disponibles, prix
) VALUES
(1, 'Legendes du hard rock', 'Hard rock et classiques', '2026-11-06', '18:00:00', 'L''Autre Canal, Nancy', 850, 32.00),
(2, 'Nuit punk rock', 'Punk rock et pop punk', '2026-11-07', '18:30:00', 'Salle Poirel, Nancy', 420, 32.00),
(3, 'Rock alternatif', 'Rock alternatif et independant', '2026-11-08', '18:00:00', 'Parc de la Pepiniere, Nancy', 1200, 32.00),
(4, 'Metal moderne', 'Metalcore et metal alternatif', '2026-11-13', '18:30:00', 'L''Autre Canal, Nancy', 850, 32.00),
(5, 'Rock francais', 'Rock francais et punk', '2026-11-14', '18:00:00', 'Salle Poirel, Nancy', 420, 32.00),
(6, 'Metal extreme', 'Heavy metal et metal industriel', '2026-11-15', '18:30:00', 'Parc de la Pepiniere, Nancy', 1200, 32.00),
(7, 'Rock des annees 80', 'Hard rock et rock classique', '2026-11-20', '18:00:00', 'L''Autre Canal, Nancy', 850, 32.00),
(8, 'Pop punk festival', 'Pop punk et punk rock', '2026-11-21', '18:30:00', 'Salle Poirel, Nancy', 420, 32.00),
(9, 'Indie rock night', 'Rock independant et garage rock', '2026-11-22', '18:00:00', 'Parc de la Pepiniere, Nancy', 1200, 32.00),
(10, 'Metal francais', 'Metal et scene francaise', '2026-11-27', '18:30:00', 'L''Autre Canal, Nancy', 850, 32.00),
(11, 'Rock alternatif international', 'Rock alternatif international', '2026-11-28', '18:00:00', 'Salle Poirel, Nancy', 420, 32.00),
(12, 'Grande nuit du rock', 'Rock et hard rock', '2026-11-29', '18:30:00', 'Parc de la Pepiniere, Nancy', 1200, 32.00);


INSERT INTO spectacle (
    id_spectacle, id_soiree, titre, `description`,
    genre, image, video, creneau
) VALUES
(1, 1, 'Metallica', 'Concert hommage a l''univers du heavy metal.', 'Heavy metal', '', '', '19:00:00'),

(2, 2, 'AC/DC', 'Un spectacle autour des classiques du hard rock.', 'Hard rock', '', '', '19:00:00'),
(3, 2, 'Bon Jovi', 'Un concert de rock arena et de grands refrains.', 'Hard rock', '', '', '21:00:00'),

(4, 3, 'Nirvana Tribute', 'Une soiree hommage a la scene grunge.', 'Grunge', '', '', '18:30:00'),
(5, 3, 'Foo Fighters', 'Un concert de rock alternatif energique.', 'Rock alternatif', '', '', '20:00:00'),
(6, 3, 'The Offspring', 'Un spectacle consacre au punk rock.', 'Punk rock', '', '', '21:30:00'),

(7, 4, 'Ice Nine Kills', 'Un spectacle de metalcore theatrale.', 'Metalcore', '', '', '19:00:00'),

(8, 5, 'Les Fatals Picards', 'Un concert de rock francais humoristique.', 'Rock francais', '', '', '19:00:00'),
(9, 5, 'Falling in Reverse', 'Un spectacle de metalcore et de rock moderne.', 'Metalcore', '', '', '21:00:00'),

(10, 6, 'Linkin Park', 'Un concert de nu metal et de rock alternatif.', 'Nu metal', '', '', '18:30:00'),
(11, 6, 'System of a Down Tribute', 'Un hommage au metal alternatif.', 'Nu metal', '', '', '20:00:00'),
(12, 6, 'Korn Tribute', 'Un spectacle hommage aux pionniers du nu metal.', 'Nu metal', '', '', '21:30:00'),

(13, 7, 'Scorpions', 'Un concert de hard rock classique.', 'Hard rock', '', '', '19:00:00'),

(14, 8, 'Green Day', 'Un spectacle de punk rock et de pop punk.', 'Punk rock', '', '', '19:00:00'),
(15, 8, 'Sum 41', 'Un concert de punk rock canadien.', 'Punk rock', '', '', '21:00:00'),

(16, 9, 'Muse', 'Un concert de rock alternatif et de grandes ambiances.', 'Rock alternatif', '', '', '18:30:00'),
(17, 9, 'Royal Blood', 'Un spectacle de rock puissant en duo.', 'Rock alternatif', '', '', '20:00:00'),
(18, 9, 'The Killers', 'Un concert de rock alternatif et independant.', 'Rock alternatif', '', '', '21:30:00'),

(19, 10, 'Gojira', 'Un spectacle de metal francais.', 'Metal progressif', '', '', '19:00:00'),

(20, 11, 'Ultra Vomit', 'Un concert de metal humoristique.', 'Metal', '', '', '19:00:00'),
(21, 11, 'Tagada Jones', 'Un spectacle de punk metal francais.', 'Punk metal', '', '', '21:00:00'),

(22, 12, 'Queen Tribute', 'Un hommage aux grands classiques de Queen.', 'Rock classique', '', '', '18:30:00'),
(23, 12, 'Guns N'' Roses', 'Un concert de hard rock americain.', 'Hard rock', '', '', '20:00:00'),
(24, 12, 'Iron Maiden', 'Un spectacle de heavy metal britannique.', 'Heavy metal', '', '', '21:30:00');


INSERT INTO spectacle_artiste (id_spectacle, id_artiste) VALUES
(1, 1),
(2, 2),
(3, 8),
(4, 3),
(5, 4),
(6, 13),
(7, 5),
(8, 9),
(9, 10),
(10, 7),
(11, 22),
(12, 28),
(13, 18),
(14, 11),
(15, 12),
(16, 15),
(17, 34),
(18, 33),
(19, 47),
(20, 48),
(21, 49),
(22, 16),
(23, 17),
(24, 19);

COMMIT;
