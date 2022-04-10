-- #! mysql
-- #{ network
-- #    { init
-- #        { table
-- #            { stats
CREATE TABLE IF NOT EXISTS PlayerStats
(
    name   VARCHAR(30) NOT NULL UNIQUE,
    `rank` TEXT,
    kills  INT,
    deaths INT,
    kdr    INT,
    ks     INT,
    bestks INT
);
-- #            }
-- #            { elo
CREATE TABLE IF NOT EXISTS PlayerElo
(
    name   VARCHAR(30) NOT NULL UNIQUE,
    NoDebuff INT,
    Boxing   INT,
    Gapple   INT,
    Sumo     INT,
    BuildUHC INT,
    Fist     INT,
    Combo    INT,
    Spleef   INT
    );
-- #            }
-- #            { settings
CREATE TABLE IF NOT EXISTS PlayerSettings
(
    name         VARCHAR(30) NOT NULL UNIQUE,
    scoreboard   BOOLEAN,
    cpscounter   BOOLEAN,
    autorequeue  BOOLEAN,
    autorekit    BOOLEAN,
    bloodfx      BOOLEAN
);
-- #            }
-- #        }
-- #    }
-- #    { load
-- #        { playerdata
-- #            :name string
SELECT *
FROM PlayerSettings
WHERE PlayerSettings.name = :name
-- #        }
-- #        { statsdata
-- #            :name string
SELECT *
FROM PlayerStats
WHERE PlayerStats.name = :name
-- #        }
-- #        { elodata
-- #            :name string
SELECT *
FROM PlayerElo
WHERE PlayerElo.name = :name
-- #        }
-- #    }
-- #    { update
-- #        { playerdata
-- #            :name string
-- #            :scoreboard bool
-- #            :cpscounter bool
-- #            :autorequeue bool
-- #            :autorekit bool
-- #            :bloodfx bool
INSERT INTO PlayerSettings (name, scoreboard, cpscounter, autorequeue, autorekit, bloodfx)
VALUES (:name, :scoreboard, :cpscounter, :autorequeue, :autorekit, bloodfx)
    ON DUPLICATE KEY UPDATE name         = :name,
                         scoreboard     = :scoreboard,
                         cpscounter   = :cpscounter,
                         autorequeue  = :autorequeue,
                         autorekit     = :autorekit,
                         bloodfx     = :bloodfx;
-- #        }
-- #        { statsdata
-- #            :name string
-- #            :rank string
-- #            :kills int
-- #            :deaths int
-- #            :kdr int
-- #            :ks int
-- #            :bestks int
INSERT INTO PlayerStats (name, `rank`, kills, deaths, kdr, ks, bestks)
VALUES (:name, :rank, :kills, :deaths, :kdr, :ks, :bestks)
    ON DUPLICATE KEY UPDATE name   = :name,
                         `rank` = :rank,
                         kills  = :kills,
                         deaths = :deaths,
                         kdr  = :kdr,
                         ks = :ks,
                         bestks = :bestks;
-- #        }
-- #        { elodata
-- #            :name string
-- #            :NoDebuff int
-- #            :Boxing int
-- #            :Gapple int
-- #            :Sumo int
-- #            :BuildUHC int
-- #            :Fist int
-- #            :Combo int
-- #            :Spleef int
INSERT INTO PlayerElo (name, NoDebuff, Boxing, Gapple, Sumo, BuildUHC, Fist, Combo, Spleef)
VALUES (:name, :NoDebuff, :Boxing, :Gapple, :Sumo, :BuildUHC, :Fist, :Combo, :Spleef)
    ON DUPLICATE KEY UPDATE name   = :name,
                         NoDebuff = :NoDebuff,
                         Boxing  = :Boxing,
                         Gapple = :Gapple,
                         Sumo  = :Sumo,
                         BuildUHC = :BuildUHC,
                         Fist = :Fist,
                         Combo = :Combo,
                         Spleef = :Spleef;
-- #        }
-- #    }
-- #}