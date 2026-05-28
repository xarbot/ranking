# Generar plantilles XLSM per Office 2021

Els usuaris finals han de descarregar una plantilla ja preparada. No han de crear macros ni enganxar codi.

Per generar aquestes plantilles al projecte cal crear una vegada `assets/vbaProject.bin` amb Excel per a Windows. Aquest binari no es pot fabricar de forma fiable amb PHP perquè VBA es guarda en format OLE compilat.

## Crear el vbaProject.bin una vegada

1. Obre `assets/plantilla-resultados-microsoft.xlsx` amb Excel 2021 per a Windows.
2. Desa una copia com a `Libro de Excel habilitado para macros (*.xlsm)`.
3. Prem `Alt + F11`.
4. Al panell esquerre, obre la fulla `Resultados`.
5. Enganxa aquest codi VBA.
6. Desa el fitxer `.xlsm`.
7. Extreu `xl/vbaProject.bin` del `.xlsm` i guarda'l com a `assets/vbaProject.bin`.
8. Executa `php scripts/package_xlsm_template.php`.

El mateix `vbaProject.bin` serveix per a les dues plantilles perquè el codi detecta la columna `Ciudad`.

```vb
Option Explicit

Private Function CityColumn() As Long
    Dim cell As Range
    For Each cell In Me.Rows(1).Cells
        If Trim$(LCase$(cell.Value)) = "ciudad" Then
            CityColumn = cell.Column
            Exit Function
        End If
        If cell.Column > 20 Then Exit Function
    Next cell
End Function

Private Function EnsureCityCombo() As OLEObject
    On Error Resume Next
    Set EnsureCityCombo = Me.OLEObjects("CityCombo")
    On Error GoTo 0

    If EnsureCityCombo Is Nothing Then
        Set EnsureCityCombo = Me.OLEObjects.Add( _
            ClassType:="Forms.ComboBox.1", _
            Link:=False, _
            DisplayAsIcon:=False, _
            Left:=0, _
            Top:=0, _
            Width:=120, _
            Height:=18)
        EnsureCityCombo.Name = "CityCombo"
        EnsureCityCombo.Visible = False
    End If
End Function

Private Sub HideCityCombo()
    On Error Resume Next
    Me.OLEObjects("CityCombo").Visible = False
    On Error GoTo 0
End Sub

Private Sub Worksheet_SelectionChange(ByVal Target As Range)
    Dim cityCol As Long
    Dim combo As OLEObject

    cityCol = CityColumn()
    If Target.CountLarge <> 1 Or cityCol = 0 Or Target.Column <> cityCol Or Target.Row < 2 Or Target.Row > 501 Then
        HideCityCombo
        Exit Sub
    End If

    Set combo = EnsureCityCombo()
    With combo
        .Left = Target.Left
        .Top = Target.Top
        .Width = Target.Width
        .Height = Target.Height
        .LinkedCell = Target.Address(False, False)
        .ListFillRange = "'Ciudades'!$A$2:$A$8133"
        .Visible = True
        .Activate
    End With

    With combo.Object
        .Style = 0
        .MatchEntry = 1
        .MatchRequired = False
        .Text = Target.Value
        .DropDown
    End With
End Sub
```

## Generar els fitxers finals

```bash
php scripts/package_xlsm_template.php
```

Aixo genera:

- `assets/plantilla-resultados-microsoft-2021.xlsm`
- `assets/plantilla-resultados-atletas-microsoft-2021.xlsm`

El selector de l'admin ja esta preparat per descarregar aquests dos `.xlsm` com a opcio Microsoft Office 2021. Abans de pujar a produccio cal comprovar que existeixen.

## Extreure vbaProject.bin

Un `.xlsm` es un ZIP. Per extreure el binari:

1. Fes una copia del `.xlsm` creat amb Excel.
2. Canvia l'extensio de la copia de `.xlsm` a `.zip`.
3. Obre el ZIP.
4. Ves a la carpeta `xl`.
5. Copia `vbaProject.bin`.
6. Enganxa'l a `assets/vbaProject.bin` dins el projecte.

## Checklist abans de desplegar

Executa:

```bash
php scripts/package_xlsm_template.php
ls -lh assets/plantilla-resultados-microsoft-2021.xlsm assets/plantilla-resultados-atletas-microsoft-2021.xlsm
```

Han d'existir aquests tres fitxers:

- `assets/vbaProject.bin`
- `assets/plantilla-resultados-microsoft-2021.xlsm`
- `assets/plantilla-resultados-atletas-microsoft-2021.xlsm`

Despres cal fer commit d'aquests binaris juntament amb els canvis de codi.
