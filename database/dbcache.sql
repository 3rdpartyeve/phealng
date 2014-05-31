/*
 MIT License
 Copyright (c) 2014 Matthias Kühne, Peter Petermann

 Permission is hereby granted, free of charge, to any person
 obtaining a copy of this software and associated documentation
 files (the "Software"), to deal in the Software without
 restriction, including without limitation the rights to use,
 copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the
 Software is furnished to do so, subject to the following
 conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.
*/

-- // If you want to use the database cache, you need to create the table
-- // If you alter the table name make sure that the DatabaseStorage-class gets the new table name

CREATE TABLE `phealng-cache` (
    `userId` INT(10) UNSIGNED NOT NULL,
    `scope` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `args` VARCHAR(250) NOT NULL,
    `xml` LONGTEXT NOT NULL,
    PRIMARY KEY (`userId`, `scope`, `name`, `args`)
)
COMMENT='Caching for PhealNG'
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
