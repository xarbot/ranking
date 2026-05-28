# Plantilla Microsoft Office 2021 amb autocompletat de ciutats

Office Professional Plus 2021 no autocompleta els desplegables de validacio de dades. Per tenir una cerca mentre s'escriu cal guardar la plantilla com a `.xlsm` i afegir un control ActiveX `ComboBox` amb VBA.

## Passos

1. Obre `plantilla-resultados-microsoft.xlsx` o `plantilla-resultados-atletas-microsoft.xlsx` amb Excel 2021.
2. Guarda una copia com a `Libro de Excel habilitado para macros (*.xlsm)`.
3. Prem `Alt + F11`.
4. Al panell esquerre, obre el modul de la fulla `Resultados`.
5. Enganxa el codi VBA seguent.
6. Guarda i tanca l'editor.
7. En tornar a Excel, habilita macros.

El codi detecta automaticament la columna `Ciudad`, per tant serveix tant per a la plantilla d'un atleta com per a la de diversos atletes.

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

## Limitacions

Aquest metode nomes funciona en Excel d'escriptori per a Windows amb macros i ActiveX habilitats. No funciona a Excel Web ni amb macros bloquejades per politica de seguretat.
