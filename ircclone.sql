SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

DROP SCHEMA IF EXISTS `ircclone` ;
CREATE SCHEMA IF NOT EXISTS `ircclone` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `ircclone` ;

-- -----------------------------------------------------
-- Table `ircclone`.`users`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ircclone`.`users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(16) NOT NULL ,
  `password` VARCHAR(60) NOT NULL ,
  `email` VARCHAR(45) NULL ,
  `permissions` INT UNSIGNED NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) )
AUTO_INCREMENT = 1;


-- -----------------------------------------------------
-- Table `ircclone`.`events`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ircclone`.`events` (
  `userid` INT UNSIGNED NOT NULL ,
  `to` VARCHAR(16) NULL ,
  `type` VARCHAR(12) NULL ,
  `message` VARCHAR(255) NULL ,
  `date` BIGINT UNSIGNED NULL ,
  PRIMARY KEY (`userid`) ,
  CONSTRAINT `events_userid`
    FOREIGN KEY (`userid` )
    REFERENCES `ircclone`.`users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ircclone`.`channels`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ircclone`.`channels` (
  `name` VARCHAR(16) NOT NULL ,
  `modes` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `topic` VARCHAR(45) NULL ,
  `password` VARCHAR(12) NULL ,
  `userlimit` INT UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`name`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `ircclone`.`user_channels`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ircclone`.`user_channels` (
  `user` INT UNSIGNED NOT NULL ,
  `channel` VARCHAR(16) NOT NULL ,
  `permissions` INT UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`user`, `channel`) ,
  INDEX `user_channels_user_idx` (`user` ASC) ,
  INDEX `user_channels_channel_idx` (`channel` ASC) ,
  CONSTRAINT `user_channels_user`
    FOREIGN KEY (`user` )
    REFERENCES `ircclone`.`users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `user_channels_channel`
    FOREIGN KEY (`channel` )
    REFERENCES `ircclone`.`channels` (`name` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

USE `ircclone` ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `ircclone`.`users`
-- -----------------------------------------------------
START TRANSACTION;
USE `ircclone`;
INSERT INTO `ircclone`.`users` (`id`, `name`, `password`, `email`, `permissions`) VALUES (0, 'SERVER', NULL, NULL, 7);

COMMIT;
