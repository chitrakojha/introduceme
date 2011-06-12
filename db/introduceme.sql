SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `person`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `person` ;

CREATE  TABLE IF NOT EXISTS `person` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `email` VARCHAR(100) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 1;


-- -----------------------------------------------------
-- Table `facebook`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `facebook` ;

CREATE  TABLE IF NOT EXISTS `facebook` (
  `id` VARCHAR(64) NOT NULL ,
  `access_token` VARCHAR(255) NULL ,
  `person_id` BIGINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_facebook_person` (`person_id` ASC) ,
  CONSTRAINT `fk_facebook_person`
    FOREIGN KEY (`person_id` )
    REFERENCES `person` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `linkedin`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `linkedin` ;

CREATE  TABLE IF NOT EXISTS `linkedin` (
  `id` VARCHAR(64) NOT NULL ,
  `access_token` VARCHAR(255) NULL ,
  `person_id` BIGINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_linkedin_person1` (`person_id` ASC) ,
  CONSTRAINT `fk_linkedin_person1`
    FOREIGN KEY (`person_id` )
    REFERENCES `person` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `introduction`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `introduction` ;

CREATE  TABLE IF NOT EXISTS `introduction` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `introducer_id` BIGINT NOT NULL ,
  `introducee1_id` BIGINT NOT NULL ,
  `introducee2_id` BIGINT NOT NULL ,
  `time` DATETIME NOT NULL ,
  `introducee1_notified` CHAR(1) NOT NULL COMMENT 'e = email, f = facebook, l = linkedin, t = twitter' ,
  `introducee2_notified` CHAR(1) NULL ,
  `introducee1_read` TINYINT(1) NOT NULL DEFAULT 0 ,
  `introducee2_read` TINYINT(1) NOT NULL DEFAULT 0 ,
  `link_password` CHAR(3) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_introduction_person1` (`introducer_id` ASC) ,
  INDEX `fk_introduction_person2` (`introducee1_id` ASC) ,
  INDEX `fk_introduction_person3` (`introducee2_id` ASC) ,
  CONSTRAINT `fk_introduction_person1`
    FOREIGN KEY (`introducer_id` )
    REFERENCES `person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_introduction_person2`
    FOREIGN KEY (`introducee1_id` )
    REFERENCES `person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_introduction_person3`
    FOREIGN KEY (`introducee2_id` )
    REFERENCES `person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `twitter`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `twitter` ;

CREATE  TABLE IF NOT EXISTS `twitter` (
  `id` VARCHAR(64) NOT NULL ,
  `access_token` VARCHAR(255) NULL ,
  `person_id` BIGINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_twitter_person1` (`person_id` ASC) ,
  CONSTRAINT `fk_twitter_person1`
    FOREIGN KEY (`person_id` )
    REFERENCES `person` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `temp_friends`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `temp_friends` ;

CREATE  TABLE IF NOT EXISTS `temp_friends` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `time` DATETIME NOT NULL ,
  `facebook_id` VARCHAR(64) NULL ,
  `linkedin_id` VARCHAR(64) NULL ,
  `twitter_id` VARCHAR(64) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_temp_friends_facebook1` (`facebook_id` ASC) ,
  INDEX `fk_temp_friends_linkedin1` (`linkedin_id` ASC) ,
  INDEX `fk_temp_friends_twitter1` (`twitter_id` ASC) ,
  CONSTRAINT `fk_temp_friends_facebook1`
    FOREIGN KEY (`facebook_id` )
    REFERENCES `facebook` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_temp_friends_linkedin1`
    FOREIGN KEY (`linkedin_id` )
    REFERENCES `linkedin` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_temp_friends_twitter1`
    FOREIGN KEY (`twitter_id` )
    REFERENCES `twitter` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `temp_friend`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `temp_friend` ;

CREATE  TABLE IF NOT EXISTS `temp_friend` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `temp_friends_id` BIGINT NOT NULL ,
  `facebook_id` VARCHAR(64) NULL ,
  `linkedin_id` VARCHAR(64) NULL ,
  `twitter_id` VARCHAR(64) NULL ,
  `name` VARCHAR(90) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_temp_friend_temp_friends1` (`temp_friends_id` ASC) ,
  CONSTRAINT `fk_temp_friend_temp_friends1`
    FOREIGN KEY (`temp_friends_id` )
    REFERENCES `temp_friends` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `message`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `message` ;

CREATE  TABLE IF NOT EXISTS `message` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `body` TEXT NOT NULL ,
  `time` DATETIME NOT NULL ,
  `introduction_id` BIGINT NOT NULL ,
  `writer_id` BIGINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_message_introduction1` (`introduction_id` ASC) ,
  INDEX `fk_message_person1` (`writer_id` ASC) ,
  CONSTRAINT `fk_message_introduction1`
    FOREIGN KEY (`introduction_id` )
    REFERENCES `introduction` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_message_person1`
    FOREIGN KEY (`writer_id` )
    REFERENCES `person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `link`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `link` ;

CREATE  TABLE IF NOT EXISTS `link` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `introduction_id` BIGINT NOT NULL ,
  `person_id` BIGINT NOT NULL ,
  `link_password` CHAR(3) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_link_introduction1` (`introduction_id` ASC) ,
  INDEX `fk_link_person1` (`person_id` ASC) ,
  CONSTRAINT `fk_link_introduction1`
    FOREIGN KEY (`introduction_id` )
    REFERENCES `introduction` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_link_person1`
    FOREIGN KEY (`person_id` )
    REFERENCES `person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Used to make a URL with name / network prefilled. 238328';


-- -----------------------------------------------------
-- Table `temp_linkedin`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `temp_linkedin` ;

CREATE  TABLE IF NOT EXISTS `temp_linkedin` (
  `linkedin_id` VARCHAR(64) NOT NULL ,
  `time` DATETIME NOT NULL ,
  `profile_url` VARCHAR(255) NULL ,
  `picture_url` VARCHAR(255) NULL ,
  PRIMARY KEY (`linkedin_id`) ,
  CONSTRAINT `fk_temp_linkedin_linkedin1`
    FOREIGN KEY (`linkedin_id` )
    REFERENCES `linkedin` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `temp_twitter`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `temp_twitter` ;

CREATE  TABLE IF NOT EXISTS `temp_twitter` (
  `twitter_id` VARCHAR(64) NOT NULL ,
  `time` DATETIME NOT NULL ,
  `screen_name` VARCHAR(45) NULL ,
  `picture_url` VARCHAR(255) NULL ,
  `protected` CHAR(1) NULL ,
  PRIMARY KEY (`twitter_id`) ,
  INDEX `fk_temp_twitter_twitter1` (`twitter_id` ASC) ,
  CONSTRAINT `fk_temp_twitter_twitter1`
    FOREIGN KEY (`twitter_id` )
    REFERENCES `twitter` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `aws_ses`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `aws_ses` ;

CREATE  TABLE IF NOT EXISTS `aws_ses` (
  `id` BIGINT NOT NULL AUTO_INCREMENT ,
  `recipient_id` BIGINT NOT NULL ,
  `ses_message_id` VARCHAR(255) NULL ,
  `ses_request_id` VARCHAR(255) NULL ,
  `introduction_id` BIGINT NULL ,
  `message_id` BIGINT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_aws_ses_person1` (`recipient_id` ASC) ,
  INDEX `fk_aws_ses_introduction1` (`introduction_id` ASC) ,
  INDEX `fk_aws_ses_message1` (`message_id` ASC) ,
  CONSTRAINT `fk_aws_ses_person1`
    FOREIGN KEY (`recipient_id` )
    REFERENCES `person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_aws_ses_introduction1`
    FOREIGN KEY (`introduction_id` )
    REFERENCES `introduction` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_aws_ses_message1`
    FOREIGN KEY (`message_id` )
    REFERENCES `message` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
