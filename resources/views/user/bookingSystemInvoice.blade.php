<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Invoice')

@section('content')

    @if (!$showInvoice)
    
        <div class="breadcrumbs">
            <ol class="breadcrumb black" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            </ol>
        </div>
    
        <div class="container">
        
            <h1 class="hero-heading h2">Invoice Generator</h1>
            <h2>{{{ $systemInfo->displayName }}}</h2>
            
            <form role="form" method="post">
            
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                
                <div class="form-group">
                    <label>
                        Password
                        <input type="password" class="form-control" name="password">
                    </label>
                    @if (Request::has('password') && Request::input('password') != $systemInfo->invoicePassword)
                        <p class="text-danger">Incorrect password.</p>
                    @endif
                </div>
                <div class="form-group">
                    <label>
                        Description
                        <input class="form-control" name="description" value="{{{ Request::has('description') ? Request::input('description') : '' }}}">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        Invoice Date
                        <input class="form-control" name="date" value="{{{ Request::has('date') ? Request::input('date') : Carbon::now()->format('Y-m-d') }}}">
                    </label>
                </div>
                        
                <div class="form-group">
                    <label>
                        Currency
                        <select class="form-control" name="currency" value="{{{ Request::has('currency') ? Request::input('currency') : '€' }}}">
                            <option>€</option>
                            <option>$</option>
                            <option>£</option>
                        </select>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        Amount
                        <input class="form-control" name="amount" value="{{{ Request::has('amount') ? Request::input('amount') : '0.00' }}}">
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">@langGet('global.Submit')</button>
            </form>
            
        </div>
        
    @else

        <div class="container">
                
                <p>
                    <div class="hidden-print text-center">
                        <button class="btn btn-primary" onclick="window.print();"><i class="fa fa-print"></i>&nbsp; Print</button>
                        <button class="btn btn-default" onclick="window.history.go(-1);">Cancel</button>
                    </div>
                </p>
                
                <div style="margin: 18px 0; border: 1px solid #aaa; border-radius:5px; padding: 18px">
                    <div class="clearfix">
                        <div class="well pull-right">
                            <table>
                                <tr>
                                    <td class="text-right"><strong>Date</strong> &nbsp;</td>
                                    <td>{{{ Request::input('date') }}}</td>
                                </tr>
                                <tr>
                                    <td class="text-right" style="vertical-align: top"><strong>Bill To:</strong> &nbsp;</td>
                                    <td style="vertical-align: top">
                                        @foreach ($systemInfo->invoiceAddress as $addressLine)
                                            <div>{{{ $addressLine }}}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <div><img src="{!! routeURL('images', 'hostelz-small.png') !!}"></div>
                            <br>
                            <div><strong>Hostelz.com</strong></div>
                            <div>Austin, TX 78759</div>
                            <div>USA</div>
                            <div>Phone: +1 512-579-9345</div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h1>Invoice</h1>
    
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <table class="table table-striped" style="margin: 0 0">
                    			<thead>
            						<tr>
            						    <th>Description</th>
            							<th>Amount</th>
            						</tr>
            					</thead>
            					<tbody>
                    				<tr>
                						<td>{{{ Request::input('description') }}}</td>
                						<td>{{{ Request::input('currency') }}} {{{ Request::input('amount') }}}</td>
                					</tr>
                                    <tr>
            							<td class="text-right"><strong>Total Due</strong></td>
            							<td><strong>{{{ Request::input('currency') }}} {{{ Request::input('amount') }}}</strong></td>
            						</tr>
            					</tbody>
                			</table>
                        </div>
                    </div>
                    
                    <br>
                    
                    <div class="well text-center">
                        <p>Thank You For Your Business!</p>
                        If you have any questions about this invoice, please contact admin@hostelz.com.
      			    </div>
                  
                </div>
                
        </div>
        
    @endif
    
@stop
