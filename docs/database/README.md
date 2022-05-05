# GemsTracker Database Documentation

## Usage
You can use the [gemstracker-database.svg](gemstracker-database.svg) and [gemstracker-database.png](gemstracker-database.png) as is.

The [gemstracker-database.dbml](gemstracker-database.dbml) can be used with many different tooles: 
any tool that supports the Database Markup Language definition:
- [dbml.org](https://www.dbml.org/) - The definition of DBML.
- [DBML Core](https://github.com/holistics/dbml) - Contains an overview of usefull tools.
- [dbdiagram.io](https://dbdiagram.io/) - Quickly make a diagram.
- [dbdocs.io](https://dbdocs.io/?) - Quickly browse your database definition
- [DBML Renderer](https://github.com/softwaretechnik-berlin/dbml-renderer) - Render to .svg


## Updating

### Recreate the dbml file

To use Phing to recreate the dbml file:
1. Go to the gemstracker/scripts directory on the command line.
1. Find the Phing command (installed with composer, either `..\bin\phing[.bat]` or `..\vendor\bin\phing[.bat]`).
1. Call Phing with the dbdiagram.io command: `..\bin\phing.bat dbdiagram.io`.

[gemstracker-database.dbml](gemstracker-database.dbml) is now updated using the latest table definitions db\tables\*.sql.

If you want to add (or remove) tables edit the `sql-structural-files` filelist in the [Phing build.xml](../../scripts/build.xml).

## Recreate the svg file
The table links overview [gemstracker-database.svg](gemstracker-database.svg) ius generated using the npm 
[DBML Renderer](https://github.com/softwaretechnik-berlin/dbml-renderer).

After installation:
1. Go to docs/database directory on the command line.
1. Run `dbml-renderer -i gemstracker-database.dbml -o gemstracker-database.svg`.

## Recreate the png file

The png file is generated using [dbdiagram.io](https://dbdiagram.io/d/6195278002cf5d186b5b4c2b). As we are using the free
version of this software it is slightly more complicated to use.

1. Go to https://dbdiagram.io/.
1. **[Create you diagram](https://dbdiagram.io/d)**.
1. Paste the content of [gemstracker-database.dbml](gemstracker-database.dbml) on the right side.
1. You get a pop-up to go Pro as we are using multiple TableGroup definitions.
1. Cancel this and remove the 5 TableGroups starting around line 9.
1. Spent about half an hour getting the tables positioned as you want them.
1. Us the **Export** button to generate the png.