/**
 * Google Apps Script to run through a chunk of clients 
 * and update their status stats and generate new statements.
 */
function updateClients(options = {})
{
  Logger.log('updateClients(), Says Hi');
  Logger.log('updateClients(), Options: ' + JSON.stringify(options));
  
  const now = new Date();
  const nowTS = now.getTime();
  const currentYear = now.getFullYear(); // A typical value: 2019
  const lastYear = currentYear - 1;
  const task = options.task || 'Update Calculated Values';
  const uid = options.uid || '_system_';  

  const reCalcAll = true;

  Logger.log('updateClients(), This year = ' + currentYear );
  Logger.log('updateClients(), Last year = ' + lastYear );

  const ss = options.ss || SpreadsheetApp.getActive();
  const clientsSheet = options.clientSheet || ss.getSheetByName('Clients');
  const clientColsIndex = options.clientColsIndex || getColsIndex(clientsSheet);
  const clientCount = clientsSheet.getMaxRows() - 1;
  const etcSheet = ss.getSheetByName('Etc');

  const chTradingFee = parseNumber(etcSheet.getRange('B7').getValue()); // R60

  let startRow = 2;
  let finalRow = clientCount + 1;
  let chunkSize = clientCount;
  let clientRowsIndex = {};
  let clientRows = []; // Array of client ROW INDEXES, not IDs!

  // If we have a "chunked update" request
  if (options.chunkSize > 0)
  {
    chunkSize = options.chunkSize;
    if (options.lastRow && options.lastRow < finalRow)
    {
      startRow = options.lastRow + 1;
      chunkSize = Math.min(finalRow - startRow + 1, chunkSize);
    }
    // Create a sequencial array of ROW INDEXES based on "row start" and "chunk size" values,
    // then assign to "clientRows"
    clientRows = Array.from({length: chunkSize}, (_, i) => i + startRow);
  }
  else if (options.clients) {
    // Reverse-resolve client table ROW INDEXES from client IDs, then push into "clientRows"
    clientRowsIndex = options.clientRowsIndex || getRowsIndex(clientsSheet, clientColsIndex.Client_ID);
    options.clients.forEach(clientID => clientRows.push(clientRowsIndex[clientID]));
  }
  else {
    return 0;
  }

  let msg = 'allocatePINs(), clientCount: ' + clientCount + ', startRow: ' + startRow + ', chunkSize: ' + chunkSize;
  Logger.log(msg);
  ss.toast(msg);

  const clients = {};
  clientRows.forEach(function(rowNum)
  {
    const clientRow = getRow(clientsSheet, rowNum);
    if ( ! clientRow || ! clientRow[0] ) return; // Don't process blank rows.
    const cid = getValue(clientRow, clientColsIndex.Client_ID);
    const name = clientName = getValue(client.clientRow, clientColsIndex.Name);
    const sdaMandate = getValue(clientRow, clientColsIndex.SDA_Mandate);
    const fiaMandate = getValue(clientRow, clientColsIndex.FIA_Mandate);
    const sdaUsed = getValue(clientRow, clientColsIndex.SDA_Used);
    const fiaUsed = getValue(clientRow, clientColsIndex.FIA_Used);
    clients[cid] = { cid, rowNum, name, sdaMandate, fiaMandate, sdaUsed, fiaUsed, tccs: [], trades: [], clientRow };
  });

  Logger.log('updateClients(), Ok, we have our clients hash map!');

  // Build a valid OVEX ID lookup list to verify if CH OVEX Trades are real and logged with OVEX.
  // We use the "OVEX Current Year" sheet to build the list, since it contains only the
  // relevant OVEX trades for the current year.

  Logger.log('updateClients(), Generate a lookup list of valid OVEX IDs for the current year.');

  // NM - 05 Jan 2023 - Considder scrapping the OVEX recon for speed sake...
  const ovexIDs = new Set(); 
  const ovexSheet = ss.getSheetByName('OVEX Current Year');
  const ovexColsIndex = getColsIndex(ovexSheet);
  const ovexSheetIDColumn = ovexSheet.getRange(2, ovexColsIndex.ID, ovexSheet.getMaxRows() - 1, 1);

  // Get a flat array of just the IDs
  ovexSheetIDColumn.getValues().forEach(function(row, index) { ovexIDs.add(row[0]); });

  Logger.log('updateClients(), Number of ovexIDs = ' + ovexIDs.size);
  Logger.log('updateClients(), ovexIDs[0] = ' + ovexIDs.values().next().value + ', type = ' + typeof ovexIDs.values().next().value);

  Logger.log('updateClients(), Get the current year\'s trades and group them by client.');
  Logger.log('updateClients(), Filter out CH OVEX trades that are not logged with OVEX.');

  const tradesSheet = ss.getSheetByName('Trades Current Year');
  const tradeColsIndex = getColsIndex(tradesSheet);

  /**
   * NOTE: We use the Array.find() function!
   * return false == continue
   * return true == break
   * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/find
   */
  tradesSheet.getDataRange().getValues().find(function(row, index)
  {
    if ( ! index) return false; // Index = 0 = The column titles row -> Ignore
    const chTradeDate = getValue(row, tradeColsIndex.Trade_Date);
    if ( ! chTradeDate ) return false;
    const chTradeDateTS = chTradeDate.getTime();

    const chTradeClientID = getValue(row, tradeColsIndex.Client_ID);
    const client = clients[chTradeClientID];
    if ( ! client ) return false;

    const id = getValue(row, tradeColsIndex.Trade_ID);

    // Filter out CH OVEX trades that are not logged with OVEX.
    const isInhouseOTC = id.toString().toUpperCase().startsWith('CH');
    if ( ! isInhouseOTC ) {
      // Only add trades to the list if they have matching OVEX rows!
      // Edit: Later added Recon_OVEX_ID. So now we check for the existance of two possible OVEX trx's.
      const reconID = getValue(row, tradeColsIndex.Recon_OVEX_ID);
      const isVerifiedOvex = ovexIDs.has(id|0) || ovexIDs.has(reconID|0);
      if ( ! isVerifiedOvex ) {
        Logger.log('updateClients(), WARNING. Trade ID: ' + id + ' NOR Recon ID: ' + reconID + ' is logged with OVEX!');
        return false;
      }
    }

    const otc = getValue(row, tradeColsIndex.OTC);
    const rowNum = getValue(row, tradeColsIndex.Row_Num);
    const type = getValue(row, tradeColsIndex['SDA_/_FIA']);
    const rate = parseNumber(getValue(row, tradeColsIndex['TUSD_Price_(R)']));
    const tusd = parseNumber(getValue(row, tradeColsIndex['TUSD_Bought_($)']));
    const inCapital = parseNumber(getValue(row, tradeColsIndex.ZAR_Sent_to_Currencies_Assist));
    const profitShareFactor = parseNumber(getValue(row, tradeColsIndex['Fee_Category_(%_Profit)']));
    const grossProfit = parseNumber(getValue(row, tradeColsIndex['Profit_(R)']));

    const netProfit = grossProfit - grossProfit * profitShareFactor - (isInhouseOTC ? 0 : chTradingFee);
    const netReturn = inCapital ? netProfit / inCapital : 0; 

    let coverData;
    let needsUpdate = false;
    let covered = reCalcAll ? 0 : parseNumber(getValue(row, tradeColsIndex.Amount_Covered));
    let coverNeeded = inCapital - covered;
    const coverDataStr = reCalcAll ? null : getValue(row, tradeColsIndex.Allocated_PINs);
    try { coverData = coverDataStr ? (JSON.parse(coverDataStr) || {}) : {}; }
    catch (e) { coverData = {}; }
    const pins = {}
    if ( ! reCalcAll && coverData.pins ) {
      if ( typeof coverData.covered === 'undefined' ) pins = coverData;
      else coverData.pins.forEach( pinData => pins[pinData.pin] = pinData.amount );
    }

    const trade = { id, rowNum, date: chTradeDate, dateTS: chTradeDateTS, type, otc, 
      amount: inCapital, rate, tusd, grossProfit, netProfit, netReturn, 
      covered, coverNeeded, pins, needsUpdate, row };

    client.trades.push(trade);
  });


  const activeTccsSheet = ss.getSheetByName('FIA Approved');
  const tccColsIndex = getColsIndex(activeTccsSheet);

  Logger.log('updateClients(), Get All the valid TCC PINs for the current year. I.e. All the "FIA Approved" PINs');
  Logger.log('updateClients(), Group them by client.');

  activeTccsSheet.getDataRange().getValues().filter(function(row, index) {
    if ( ! index ) return false; // Index = 0 = The column titles row -> Ignore
    const tccDate = getValue(row, tccColsIndex.Date);
    if ( ! tccDate ) return false;
    const dateTS = tccDate.getTime();
    let expiryDate = new Date(tccDate); // Clone, so we don't change the orignal.
    expiryDate.setFullYear(expiryDate.getFullYear() + 1);
    const expiryTS = expiryDate.getTime();
    const isExpired = expiryTS < nowTS;    
    const clientID = getValue(row, tccColsIndex.Client_ID);
    const client = clients[clientID];
    if ( ! client ) return false;
    const status = getValue(row, tccColsIndex.Status);
    const pinNumber = getValue(row, tccColsIndex.TCC_PIN);
    const amountCleared = getValue(row, tccColsIndex.Amount_Cleared);
    const rollover = getValue(row, tccColsIndex.Rollover);
    const expired =  getValue(row, tccColsIndex.Expired);
    const amountReserved =  getValue(row, tccColsIndex.Amount_Reserved);
    let amountClearedNet = getValue(row, tccColsIndex.Amount_Cleared_Net);
    let amountUsed = reCalcAll ? 0 : getValue(row, tccColsIndex.Amount_Used);
    let amountRemaining = amountClearedNet - amountUsed;
    let amountAvailable = isExpired ? 0 : amountRemaining;
    const isRollover = tccRollover > 0;
    // We assume that rollover amounts already have the amount initially reserved subtracted.
    const allocDataStr = getValue(row, tccColsIndex.Allocated_Trades);
    let allocData;
    let needsUpdate = false;
    try { allocData = allocDataStr ? (JSON.parse(allocDataStr) || {}) : {}; }
    catch (e) { allocData = {}; }
    const trades = {};
    if ( ! reCalcAll && allocData.trades ) {
      if ( typeof trades.allocated === 'undefined' ) trades = allocData;
      else allocData.trades.forEach( function(trade) { trades[trade.id] = trade.amount;  } );
    }
    if ( isExpired && tccStatus !== 'Expired') needsUpdate = true;
    const rowNum = getValue(row, tccColsIndex.Row_Num);
    const tcc = { clientID, rowNum, dateTS, tccDate, status, pinNumber,
     amountCleared, rollover, amountReserved, amountClearedNet, amountUsed,
     amountRemaining, amountAvailable, expiryDate, expiryTS,
     expired, isExpired, trades, needsUpdate, row };

    client.tccs.push(tcc);
  });

  Logger.log('updateClients(), Grab all the sheets we need to write to!');

  const clientsMasterSheet = ss.getSheetByName('Clients');
  const tradesMasterSheet = ss.getSheetByName('Currency Hub Trades');
  const tccsMasterSheet = ss.getSheetByName('FIA TAX Clearances');

  Logger.log('updateClients(), OK. Let`s start the main loop.');
  Logger.log('updateClients(), Update each client`s Active TCCs and accociated Trades.');

  // Let's cycle through each client and run their tcc/trade updates and stat calculations.
  // We need to save updates to TCCs and Trades back to their source data sheets after updates,
  // so we will need to keep track of which TCCs and Trades have been updated.
  // We will do this by creating an array of TCCs and Trades that have been updated.
  let lastRow = 0;
  for ( const cid in clients )
  {
    const client = clients[cid];
    const rowNum = client.rowNum;

    Logger.log('updateClients(), ********************************************');
    Logger.log('updateClients(), Processing: ' + client.name + ' [' + cid + ']');
    Logger.log('updateClients(), ********************************************');


    // Create a working copy of client.trades sorted by date (oldest first).
    let tradesThatNeedCover = client.trades.filter(trade => trade.coverNeeded > 0)
      .sort((a,b) => a.dateTS - b.dateTS);

    if (reCalcAll) client.sdaUsed = 0;
    client.sdaRemaining = Math.min(1000000, client.sdaMandate) - client.sdaUsed;

    let tradeIndex = 0;
    while (tradeIndex < tradesThatNeedCover.length && sdaRemaining > 0)
    {
      Logger.log('updateClients(), Trades that need cover: ' + ( tradesThatNeedCover.length - tradeIndex ) );
      Logger.log('updateClients(), sdaRemaining: ' + sdaRemaining);

      const trade = tradesThatNeedCover[tradeIndex];

      // If the trade has not been fully allocated, let's see if we can allocate some more...
      const amountToAllocate = Math.min(client.sdaRemaining, trade.coverNeeded);
      if ( amountToAllocate > 0 ) // If we can allocate some more...
      {
        if ( trade.pins.__SDA__ > 0 ) trade.pins.__SDA__ += amountToAllocate;
        else trade.pins.__SDA__ = amountToAllocate;         
        if ( trade.covered > 0 ) trade.covered += amountToAllocate;
        else trade.covered = amountToAllocate;
        trade.coverNeeded = trade.amount - trade.covered;
        client.sdaRemaining -= amountToAllocate;
        client.sdaUsed += amountToAllocate;
        trade.needsUpdate = true;
      }
      
      tradeIndex++;

    } // while (tradeIndex < tradesThatNeedCover.length && sdaRemaining > 0)

    
    tradesThatNeedCover = tradesThatNeedCover.filter(trade => trade.coverNeeded > 0);   

    if (reCalcAll) client.fiaUsed = 0;
    client.fiaRemaining = Math.min(10000000, client.fiaMandate) - client.fiaUsed;

    // Create a working copy of client.tccs sorted by date (oldest first).
    const clientTccsSortedByDate = client.tccs.sort((a,b) => a.tccDateTS - b.tccDateTS);

    clientTccsSortedByDate.forEach(tcc => {

      // Logger.log('updateClients(), Allocate TCC_' + tcc.tccPinNumber + ', value = R' + tcc.tccAmountCleared);

      let tradeIndex = 0;
      while (tradeIndex < tradesThatNeedCover.length && tcc.amountRemaining > 0)
      {
        Logger.log('updateClients(), Trades that need cover: ' + ( tradesThatNeedCover.length - tradeIndex ) );
        Logger.log('updateClients(), TCC amount remaining: ' + tcc.amountRemaining);
        Logger.log('updateClients(), TCC status: ' + tcc.status);

        const trade = tradesThatNeedCover[tradeIndex];
  
        // TCCs can NOT cover trades that happened BEFORE they were issued!
        if ( trade.dateTS < tcc.dateTS ) { tradeIndex++; continue; }

        // TCCs can NOT cover trades that occurred AFTER they expired!
        if ( trade.dateTS >= tcc.expiryTS ) { tradeIndex++; continue; }

        // If the trade has not been fully allocated, let's see if we can allocate some more...
        const amountToAllocate = Math.min(tcc.amountRemaining, trade.coverNeeded);
        if ( amountToAllocate > 0 ) // If we can allocate some more...
        {
          // Update the tccAllocated object
          tcc.amountUsed += amountToAllocate;
          tcc.amountRemaining -= amountToAllocate;
          tcc.trades[trade.id] = amountToAllocate;
          tcc.needsUpdate = true;

          // Update the trade's tradeAllocatedPins object      
          if ( trade.covered > 0 ) trade.covered += amountToAllocate;
          else trade.covered = amountToAllocate;
          trade.coverNeeded = trade.amount - trade.covered;
          client.fiaRemaining -= amountToAllocate;
          client.fiaUsed += amountToAllocate;
          trade.needsUpdate = true;
        }
        
        tradeIndex++;

      } // while (tradeIndex < tradesThatNeedCover.length && tccAllocated.remaining > 0)

      // Clean up the tradesThatNeedCover array. Remove all the covered entries.
      tradesThatNeedCover = tradesThatNeedCover.filter(trade => trade.coverNeeded > 0);      

      Logger.log('updateClients(), Processed TCC_' + tcc.pinNumber + '... value = R' + 
        tcc.amountCleared + ', remaining = R' + tcc.amoutRemaining + ', expires ' + 
        toDateString(tcc.expiryDate) + '. ' + (tcc.isExpired ? 'EXPIRED' : 'Still ACTIVE'));

      Logger.log('updateClients(), TCC needs update: ' + (tcc.needsUpdate ? 'Yes' : 'No'));

      if ( tcc.needsUpdate )
      {
        tcc.row[ tccColsIndex.Amount_Cleared_Net - 1 ] = tcc.amountClearedNet;
        tcc.row[ tccColsIndex.Amount_Used - 1 ] = tcc.amountUsed;
        tcc.row[ tccColsIndex.Amount_Remaining - 1 ] = tcc.amountRemaining
        tcc.row[ tccColsIndex.Amount_Available - 1 ] = tcc.amountAvailable;
        const expireTcc = tcc.isExpired && tcc.status !== 'Expired';
        if ( expireTcc )
        {
          tcc.row[ tccColsIndex.Status - 1 ] = 'Expired';
          tcc.row[ tccColsIndex.Expired - 1 ] = currentYear;
        }
        tcc.row[ tccColsIndex.Updated_at - 1 ] = new Date();
        tcc.row[ tccColsIndex.Updated_by - 1 ] = uid;
        const tccTradesJson = JSON.stringify(tcc.trades);
        tcc.row[ tccColsIndex.Allocated_Trades - 1 ] = tccTradesJson;
        const rowValues = tcc.row.slice(0, -1); // Drop the last column. i.e. RowNum
        Logger.log('updateClients(), ' + (expireTcc ? 'Expire' : 'Save') + 
          ' TCC_' + tcc.pinNumber + ' to tccsMasterSheet:' + tccTradesJson);
        // Save changes: i.e. Replace the entire row with the updated tcc.row
        tccsMasterSheet.getRange(tcc.rowNum, 1, 1, tccsMasterSheet.getLastColumn() - 1).setValues([rowValues]);
      }

    }); // END client.tccs.forEach(tcc => { ... });

    client.trades.forEach(function(trade)
    {
      if ( trade.needsUpdate )
      {
        const tradePinsJson = JSON.stringify(trade.pins);
        Logger.log('updateClients(), Update Trade #' + trade.id + ': ' + tradePinsJson);
        // Let's update the trade.row array with the latest allocation values.
        trade.row[ tradeColsIndex.Amount_Covered - 1 ] = trade.covered;
        trade.row[ tradeColsIndex.Allocated_PINs - 1 ] = tradePinsJson;
        trade.row[ tradeColsIndex.Updated_at - 1 ] = new Date();
        trade.row[ tradeColsIndex.Updated_by - 1 ] = uid;
        const rowValues = trade.row.slice(0, -1); // Remove the last column. i.e. RowNum!
        // Save changes: i.e. Replace the entire row with the updated trade.row
        tradesMasterSheet.getRange(trade.rowNum, 1, 1, tradesMasterSheet.getLastColumn() - 1).setValues([rowValues]);
      }
    });

    Logger.log('updateClients(), Calculate FIA Approved, FIA Pending & FIA Declined...');

    let rollovers = 0;
    let rolloversAmount = 0;
    let newTccs = 0;
    let newTccsAmount = 0;
    let fiaApproved = 0;
    let fiaAvailable = 0;
    let fiaUnused = 0;
    client.tccs.forEach(tcc => {
      const isRollover = tcc.tccRollover > 0;
      // NB: NOT the same as fiaApprovedRemaining! fiaUnused don't care about expiries.
      fiaUnused += tcc.tccAmountRemaining;
      fiaAvailable += tcc.tccAmountAvailable;
      // We assume that rollover amounts already have the amount initially reserved subtracted.
      if (isRollover) { rollovers++; rolloversAmount += tcc.tccRollover; }
      else { newTccs++; newTccsAmount += tcc.tccAmountCleared }
      // NB: We don't count unused tcc allowance amounts or reserved amounts to make Dave's spreadsheet work.
      fiaApproved += tcc.tccIsExpired ? tcc.tccAmountUsed : tcc.tccAmountClearedNet;
    });

    const tccsPendingSheet = ss.getSheetByName('FIA Pending');
    const tccsDeclinedSheet = ss.getSheetByName('FIA Declined');

    const pendingTccs = tccsPendingSheet.getDataRange().getValues().filter((row, index) => {
      if ( !index || !row.length ) return false; // Drop titles row and any blank rows
      return true; });

    const declinedTccs = tccsDeclinedSheet.getDataRange().getValues().filter((row, index) => {
      if ( !index || !row.length ) return false; // Drop titles row and any blank rows
      const applyDate = getValue(row, tccColsIndex.Application_Date);
      return ( applyDate && getYear(applyDate) == currentYear ); });

    const fiaPending = pendingTccs.reduce( function(sum, tcc) {
      if ( getValue( tcc, tccColsIndex.Client_ID ) !== cid ) return sum;
      return sum + getValue( tcc, tccColsIndex.Amount_Cleared ); }, 0);

    const fiaDeclined = declinedTccs.reduce( function(sum, tcc) {
      if ( getValue( tcc, tccColsIndex.Client_ID ) !== cid ) return sum;
      return sum + getValue( tcc, tccColsIndex.Amount_Cleared ); }, 0);

    const fiaUsed = client.trades.reduce((sum, trade) => { return sum += trade.type === 'FIA' ? trade.amount : 0; }, 0);
    const sdaUsed = client.trades.reduce((sum, trade) => { return sum += trade.type === 'SDA' ? trade.amount : 0; }, 0);

    Logger.log('client: ' + JSON.stringify({ cid, rowNum, name: client.name, sdaUsed, fiaUsed, fiaApproved, 
      fiaUnused, fiaAvailable, newTccs, newTccsAmount, rollovers, rolloversAmount }));

    Logger.log('updateClients(), Save Client changes to clientsMasterSheet...');

    const clientRowPart1 = [];
    clientRowPart1.push(fiaApproved);
    clientRowPart1.push(sdaUsed);
    clientRowPart1.push(fiaUsed);

    const clientRowPart2 = [];
    clientRowPart2.push(fiaPending);
    clientRowPart2.push(fiaDeclined);

    const clientRowPart3 = [];
    clientRowPart3.push(task);
    clientRowPart3.push(now);
    clientRowPart3.push(uid);
    clientRowPart3.push(now);
    clientRowPart3.push(uid);

    updateRow(clientsMasterSheet, rowNum, clientRowPart1, clientColsIndex.FIA_Approved, 3);
    updateRow(clientsMasterSheet, rowNum, clientRowPart2, clientColsIndex.FIA_Pending, 2);
    updateRow(clientsMasterSheet, rowNum, clientRowPart3, clientColsIndex.Last_Action, 5);

    if (options.generateStatement)
    {
      const tradesStr = 'trades = ' + JSON.stringify(client.trades);
      Logger.log('updateClients(), Generate Statement for [' + cid + ']. ' + tradesStr);
      const links = generateStatement(ss, client.clientRow, clientColsIndex, client.trades, options.statementFileUrl);

      const clientRowPart4 = [];
      clientRowPart4.push(links.smtUrl);
      clientRowPart4.push(links.pdfUrl)

      updateRow(clientsMasterSheet, rowNum, clientRowPart4, clientColsIndex.Statement_File, 2);
    }

    if (rowNum > lastRow) { lastRow = rowNum; }
  }

  Logger.log('updateClients(), Last row = ' + lastRow);
  return lastRow;
}