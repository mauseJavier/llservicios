
             
              
              <!doctype html>
                <html lang="en">
                <head>
                <meta charset="UTF-8">
                <title class="titulo">RECIBO DE SUELDO MUNICIPAL</title>

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

                       
                        .tiulo{
                        color: rgb(194,74,50);
                        margin:1cm 0cm;
                        }

                    footer {
                    position: fixed;
                    bottom: -40px;
                    left: 0px;
                    right: 0px;
                    height: 50px;

                    /** Extra personal styles **/
                    /* background-color: #03a9f4;
                    color: white;
                    text-align: center;
                    line-height: 35px; */
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
                    <img src="{{$direccionLogo}}"  alt="" width="120" height="100" style="margin-top: 20px; margin-left: 0px;"/>
                            <tr>
                            <td><h1>{{$datos->empleador}}</h1></td>   
                            </tr>
                    

             
                        <ul style="list-style: none;margin-top: -60px; font-size: 150%; margin-left: -40px; ">
                            
                            <li><small>Direccion: <strong>{{$datos->direccion_empleador}} </strong></small></li>
                    
                            <li><small>Telefono: <strong>{{$datos->telefono_empleador}} </strong></small></li>
                          
                            <li><small>Cuil: <strong>{{$datos->cuil}} </strong></small></li>

                            <li><small>Codigo postal: <strong>{{$datos->cp_empleador}} </strong></small></li>


                        </ul>

                        



                    <td>  
                        <h2 style="font-size: 300%; margin-top: -60px; margin-left: 10px;">RECIBO</h2>    
                        
                        <ul style="list-style: none; font-size: 130%; margin-left: -20px; ">
                            
                            <li><small>Legajo: <strong>{{$datos->legajo}} </strong></small></li>
                    
                            <li><small>Revista: <strong>{{$datos->revista}} </strong></small></li>
                          
                            <li><small>Categoria: <strong>{{$datos->categoria}} </strong></small></li>

                            <li><small>Funcion: <strong>{{$datos->funcion}} </strong></small></li>

                            <li><small>Sector: <strong>{{$datos->sector_empleador}} </strong></small></li>
                        </ul>

                        <div style="width: 100px;" style="height: 40px; " style="border: 1px solid black;" style="background-color:rgb(191, 182, 182)" >
                            <small>Periodo abonado</small>
                            <hr>
                            <h3 style="margin: 5px;">{{$datos->periodo}}</h3>
                        </div>
                    </td>
                    </tr>    

                </table>
                <hr>
                

                <table width="100%" style="text-align: center;">
                    <tr>
                        <td>  
                            <ul style="list-style-type: none;">
                                <li><small>Nombre: <strong>{{$datos->apellido_nombre}} </strong></small></li>
                                <li><small>Cuil: <strong>{{$datos->cuil}} </strong></small></li>
                                <li><small>Categoria: <strong>{{$datos->categoria}}</strong></small></li>
                                <li><small>Tipo Liquidacion <strong>Liquidacion Mensual</strong></small></li>

                            </ul>
                        </td>
                        <td>  
                            <ul style="list-style-type: none;">
                                <li><strong>Fecha de Pago:</strong>$datos->fecha_pago</li>
                                <li><strong>Datos acred: BPN CBU:</strong>{{$datos->cbu}}</li>
                                <li><strong>Fecha de ingreso:</strong>{{$datos->fecha_ingreso}}</li>
                                

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


                        @foreach ($mapeoIngresos as $item)


                        <tr>
                            <th scope="row">{{$item['codigo']}}</th>
                            <td>{{$item['descripcion']}}</td>
                            <td align="right">{{$item['cantidad']}}</td>
                            <td align="right">{{$item['importe']}}</td>
                            <td align="right"></td>
                        </tr>
                            
                        @endforeach
                

                        @foreach ($mapeoDeducciones as $item)

                        <tr>
                            <th scope="row">{{$item['codigo']}}</th>
                            <td align="left">{{$item['descripcion']}}</td> 
                            <td align="right">{{$item['cantidad']}}</td>
                            <td align="right"></td>
                            <td align="right">{{$item['importe']}}</td>
                        </tr>
                            
                        @endforeach          

            
                </tbody>

                    <tfoot>

                        <tr class="bill-row row-details">
                        <td>
                            <div>

                            </div>
                        </td>
                        <td>
                            <div>

                            </div>
                        </td>
                        <td>
                            <div>

                            </div>
                        </td>
                        <td>
                            <div>

                            </div>
                        </td>
                        <td>
                            <div>
                            <div class="row text-left margin-b-10 ">

                                @foreach ($mapeoTotal as $item)

                                <h3 class="gray" align="right" >{{$item['descripcion']}}: ${{$item['importe']}}</h3> 
                                    
                                @endforeach  


                            </div>

                            </div>
                        </td>
                        </tr>


                    </tfoot>
                </table>

                    <footer>
                        <div class="row text-left margin-b-10 ">


                        </div>
                    </footer>

                
                </body>
                </html>


