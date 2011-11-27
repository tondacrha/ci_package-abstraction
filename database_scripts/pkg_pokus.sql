--CREATE TABLE pokus(
--    pokus_id NUMBER PRIMARY KEY,
--    label    VARCHAR2(512),
--    cfile    CLOB,
--);


CREATE OR REPLACE PACKAGE PKG_POKUS_1001 AS
    
    PROCEDURE INSERT_POKUS(
    
        PV$label IN VARCHAR2,
        PCL$cfile IN CLOB,
		PN$rownum IN NUMBER,

		PCUR$pokus OUT SYS_REFCURSOR,
		PN$number  OUT NUMBER,
		PV$varchar OUT VARCHAR2
    );

END PKG_POKUS_1001;
/

CREATE OR REPLACE PACKAGE BODY PKG_POKUS_1001 IS
   /**
    * Just for test.
    */
    PROCEDURE INSERT_POKUS(
    
        PV$label IN VARCHAR2,
        PCL$cfile IN CLOB,
		PN$rownum IN NUMBER,

		PCUR$pokus OUT SYS_REFCURSOR,
		PN$number  OUT NUMBER,
		PV$varchar OUT VARCHAR2
    ) IS
    
    BEGIN
    
--        RAISE_APPLICATION_ERROR(-20001, 'Tohle je chyba raisla z plsql');
--        INSERT INTO pokus(pokus_id, label, cfile) VALUES(seq_pokus.nextval, PV$label, PCL$cfile);

		OPEN PCUR$pokus FOR SELECT * FROM pokus where rownum <= PN$rownum ORDER BY pokus_id DESC;
		PN$number := 111;
		PV$varchar := 'abcdefgh';
    
    END INSERT_POKUS;


END PKG_POKUS_1001;
/

