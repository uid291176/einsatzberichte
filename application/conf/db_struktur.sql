-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_config`
--

CREATE TABLE `fwef_tbl_config` (
  `id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_database_log`
--

CREATE TABLE `fwef_tbl_database_log` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `method` varchar(255) NOT NULL,
  `tablename` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `sqlquery` text NOT NULL,
  `tstamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_einsaetze`
--

CREATE TABLE `fwef_tbl_einsaetze` (
  `id` int(11) NOT NULL,
  `eid` varchar(16) NOT NULL,
  `enr` varchar(16) NOT NULL,
  `haupt_stichwort` varchar(255) NOT NULL,
  `stichwort` varchar(255) NOT NULL,
  `beginn` int(16) NOT NULL,
  `alarm` int(16) NOT NULL,
  `adresse1` tinytext NOT NULL,
  `adresse2` tinytext NOT NULL,
  `hnr` varchar(255) NOT NULL,
  `plz` varchar(16) NOT NULL,
  `ort` varchar(255) NOT NULL,
  `ortsteil` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `land` varchar(255) NOT NULL,
  `uid` int(8) NOT NULL,
  `status` varchar(255) NOT NULL,
  `typ` varchar(255) NOT NULL,
  `objektname` tinytext NOT NULL,
  `bericht_abgeschlossen` int(16) NOT NULL,
  `anrufer_anrufzeit` int(16) NOT NULL,
  `anrufer_telefonnummer` varchar(255) NOT NULL,
  `anrufer_vorname` varchar(255) NOT NULL,
  `anrufer_name` varchar(255) NOT NULL,
  `peronal_vorname` varchar(255) NOT NULL,
  `personal_name` varchar(255) NOT NULL,
  `personal_kuerzel` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_einsaetze_eav`
--

CREATE TABLE `fwef_tbl_einsaetze_eav` (
  `id` int(11) NOT NULL,
  `attribut` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `eid` varchar(16) NOT NULL,
  `enr` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_einsaetze_personal`
--

CREATE TABLE `fwef_tbl_einsaetze_personal` (
  `id` int(11) NOT NULL,
  `eid` varchar(16) NOT NULL,
  `enr` varchar(16) NOT NULL,
  `f_kenner` varchar(255) NOT NULL,
  `e_zeit` int(8) NOT NULL COMMENT 'Angabe in Minuten',
  `aus_h` int(4) NOT NULL,
  `aus_g` int(4) NOT NULL,
  `aus_m` int(4) NOT NULL,
  `aus_ff` int(4) NOT NULL,
  `eingesetzt_h` int(4) NOT NULL,
  `eingesetzt_g` int(4) NOT NULL,
  `eingesetzt_m` int(4) NOT NULL,
  `eingesetzt_ff` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_einsaetze_protokoll`
--

CREATE TABLE `fwef_tbl_einsaetze_protokoll` (
  `id` int(11) NOT NULL,
  `eid` varchar(16) NOT NULL,
  `enr` varchar(16) NOT NULL,
  `zeit` char(255) NOT NULL,
  `inhalt` mediumtext NOT NULL COMMENT 'Angabe in Minuten',
  `ersteller` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_einsaetze_ressourcen`
--

CREATE TABLE `fwef_tbl_einsaetze_ressourcen` (
  `id` int(11) NOT NULL,
  `eid` varchar(16) NOT NULL,
  `enr` varchar(16) NOT NULL,
  `organisation` text NOT NULL,
  `f_kenner` varchar(255) NOT NULL,
  `standort` varchar(255) NOT NULL,
  `alarm_beginn` int(16) NOT NULL,
  `alarm` int(16) NOT NULL,
  `f3` int(16) NOT NULL,
  `f4` int(16) NOT NULL,
  `f1` int(16) NOT NULL,
  `f2` int(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_error_log`
--

CREATE TABLE `fwef_tbl_error_log` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `occured` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trace` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fwef_tbl_user`
--

CREATE TABLE `fwef_tbl_user` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `clear` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `vorname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `deleted` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `fwef_tbl_config`
--
ALTER TABLE `fwef_tbl_config`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_database_log`
--
ALTER TABLE `fwef_tbl_database_log`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_einsaetze`
--
ALTER TABLE `fwef_tbl_einsaetze`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_einsaetze_eav`
--
ALTER TABLE `fwef_tbl_einsaetze_eav`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_einsaetze_personal`
--
ALTER TABLE `fwef_tbl_einsaetze_personal`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_einsaetze_protokoll`
--
ALTER TABLE `fwef_tbl_einsaetze_protokoll`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_einsaetze_ressourcen`
--
ALTER TABLE `fwef_tbl_einsaetze_ressourcen`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_error_log`
--
ALTER TABLE `fwef_tbl_error_log`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fwef_tbl_user`
--
ALTER TABLE `fwef_tbl_user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_config`
--
ALTER TABLE `fwef_tbl_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_database_log`
--
ALTER TABLE `fwef_tbl_database_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_einsaetze`
--
ALTER TABLE `fwef_tbl_einsaetze`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_einsaetze_eav`
--
ALTER TABLE `fwef_tbl_einsaetze_eav`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_einsaetze_personal`
--
ALTER TABLE `fwef_tbl_einsaetze_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_einsaetze_protokoll`
--
ALTER TABLE `fwef_tbl_einsaetze_protokoll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_einsaetze_ressourcen`
--
ALTER TABLE `fwef_tbl_einsaetze_ressourcen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_error_log`
--
ALTER TABLE `fwef_tbl_error_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `fwef_tbl_user`
--
ALTER TABLE `fwef_tbl_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;