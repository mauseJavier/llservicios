<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RECIBO DE SUELDO MUNICIPAL</title>

<style type="text/css">
    * {
        font-family: Verdana, Arial, sans-serif;
    }
    table{
        font-size: x-small;
    }
    tfoot tr td{
        font-weight: bold;
        /* font-size: x-small; */

    }

    .gray {
        background-color: lightgray
    }

    .centered-cell {
        text-align: center; /* Centers content horizontally */
        margin: auto;
        
        /* border: 3px solid black; */
        }

        .centered-cell:nth-child(2) { /* Target the second td */
        display: flex;
        align-items: center;
        }


</style>

</head>
<body>
  
  <table width="100%">

    {{-- <tr >
        <td colspan="3" style="text-align: center;">
            
            <small style="text-align: center;"></small> 
        </td> 
    </tr> --}}
    <tr style="align-content: center; margin-top: -80px; align-items: center;">
            <tr>
              <td><h1>MUNICIPALIDAD DE LAS LAJAS</h1></td>              
            </tr>
      <td  style="width: 250px; "  >
          <img src="./immg/LOGO.jpeg"  alt="" width="80" style="margin-top: -10px; margin-left: auto;"/>
         
         
          <table style="text-align: left;">
            
            <tr>
               <td>Direccion: Las Lajas SAAVEDRA 474</td>
            </tr>
            <tr>
              <td>Tel: 2942-499424</td>
            </tr>
            <tr>
               <td>Cuit: 30999251524</td>
            </tr>
            <tr>
               <td>Cp: 8347</td>
            </tr>
          </table>

      </td>
        



      <td class="centered-cell">  
        <h2 style="font-size: 300%; margin-top: -80px; margin-left: auto;">RECIBO</h2>    
          <ul style="list-style: none; font-size: 80%;  margin-left: auto;">
            <li>
              Legajo: 1674
            </li>
            <li>
              Revista: Permanente
            </li>
            <li>
              Func: ADMINISTTRATIVA
            </li>
            <li>
              Sector: SEC.DE HACIENDA Y AD
            </li>
          </ul>

          <div style="width: 100px;" style="height: 40px; " style="border: 1px solid black;" style="background-color:rgb(191, 182, 182)" >
            <small>Periodo abonado</small>
            <hr>
            <h3 style="margin: 5px;">02/2024</h3>
          </div>
      </td>
    </tr>    

  </table>
  <hr>
  

  <table width="100%" style="text-align: center;">
    <tr>
        <td>  
            <ul style="list-style-type: none;">
                <li><small> <strong>Nombre y Apellido: </strong>{{$recibo->cuil}}</small></li>
                <li><small>Categoria: <strong>O.F C(14)</strong></small></li>
                <li><small>Tipo Liquidacion <strong>Liquidacion Mensual</strong></small></li>

            </ul>
        </td>
        <td>  
            <ul style="list-style-type: none;">
                <li><strong>Fecha de Pago: 01/03/24</strong></li>
                <li>Datos acred: BPN CBU 1234567890</li>
                <li>Fecha de ingreso: 01/07/22</li>
                

            </ul>
        </td>
    </tr>

  </table>

  <br/>

  <table width="100%">
    <thead style="background-color: lightgray;">
      <tr  style="">
        <th>Cod</th>
        <th>Conceptos</th>
        <th>Unidades</th>
        <th>Haberes</th>
        <th>Retenciones</th>
      </tr>
    </thead>

    <tbody>



<tr>
  <th scope="row">1</th>
  <td>BASICO</td>
  <td align="right">29</td>
  <td align="right">138,937.19</td>
  <td align="right"> </td>
</tr>
    


<tr>
  <th scope="row">2</th>
  <td>ANTIGUEDAD</td>
  <td align="right">1</td>
  <td align="right">3754.27</td>
  <td align="right"></td>
</tr>
    
<tr>
  <th scope="row">10</th>
  <td>TITULO SECUNDARIO 5 AÑOS</td>
  <td align="right">1</td>
  <td align="right">24109.09</td>
  <td align="right"></td>
</tr>

<tr>
  <th scope="row">47</th>
  <td>PRESENTISMO</td>
  <td align="right">1</td>
  <td align="right">80</td>
  <td align="right"></td>
</tr>


<tr>
  <th scope="row">47</th>
  <td>HORAS EXTRAS AL 100%</td>
  <td align="right">1</td>
  <td align="right">23590.23</td>
  <td align="right"></td>
</tr>
//DESCUENTOS
<tr>
  <th scope="row">201</th>
  <td align="left">JUBILACION</td> <td align="right">1</td>
  <td align="right"></td>
  <td align="right">95869.83</td>
</tr>

<tr>
  <th scope="row">202</th>
  <td>SEGURO DE VIDA</td>
  <td align="right"></td>
  <td align="right"></td>
  <td align="right">1190.08</td>
</tr>


<tr>
  <th scope="row">301</th>
  <td>HIJOS</td>
  <td align="right">2</td>
  <td align="right"></td>
  <td align="right">5000.29</td>
</tr>
    </tbody>

    <tfoot>


        <tr>
            <td colspan="3"></td>
            <td align="right" class="gray">381.676.51</td>
            <td align="right" class="gray">200676.51</td>
        </tr>

        <tr class="bill-row row-details">
          <td>
            <div>

            </div>
          </td>
          <td>
            <div>
              <div class="row text-left margin-b-10 ">
                <h3 class="gray" >Total de aportes: 512.290,15</h3> 
                <h3 class="gray" >Total exento   32.941,63 </h3> 
                <h3 class="gray">Neto a cobrar   38.676,51</h3s> 
              </div>

            </div>
          </td>
        </tr>


    </tfoot>
  </table>

               



  
</body>
</html>