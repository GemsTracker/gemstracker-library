SET UNICODE=ON.
SHOW LOCALE.
PRESERVE LOCALE.
SET LOCALE='en_UK'.

GET DATA
 /TYPE=TXT
 /FILE="test.dat"
 /DELCASE=LINE
 /DELIMITERS=","
 /QUALIFIER="'"
 /ARRANGEMENT=DELIMITED
 /FIRSTCASE=1
 /IMPORTCASE=ALL
 /VARIABLES=
 text A9
 list F5.4
 list2 A1.
CACHE.
EXECUTE.

*Define variable labels.
VARIABLE LABELS text 'Enter some text'.
VARIABLE LABELS list 'Choose one'.
VARIABLE LABELS list2 'Choose something'.

*Define value labels.
VALUE LABELS list
1 'Yes'
2 'No'.

VALUE LABELS list2
"1" 'Yes'
"a" 'No'.

RESTORE LOCALE.
