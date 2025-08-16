import 'dart:convert';

import 'package:calendar_view/calendar_view.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

const String apiUrl = "https://magical.stefbart.ch/api.php";

Future<void> saveEventToApi(String title, DateTime start, DateTime end) async {
  final response = await http.post(
    Uri.parse("$apiUrl?action=create_event"),
    headers: {"Content-Type": "application/json"},
    body: jsonEncode({
      "title": title,
      "start": start.toIso8601String(),
      "end": end.toIso8601String(),
    }),
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    if (data["success"] == true) {
      print("Événement enregistré en DB avec ID ${data['id']}");
    } else {
      print("Erreur côté API: ${data['error']}");
    }
  } else {
    print("Erreur réseau: ${response.statusCode}");
  }
}

class HomePage extends StatefulWidget {
  const HomePage({super.key, required this.title});

  // This widget is the home page of your application. It is stateful, meaning
  // that it has a State object (defined below) that contains fields that affect
  // how it looks.

  // This class is the configuration for the state. It holds the values (in this
  // case the title) provided by the parent (in this case the App widget) and
  // used by the build method of the State. Fields in a Widget subclass are
  // always marked "final".

  final String title;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {

  final EventController _eventController = EventController();

  @override
  void dispose() {
    _eventController.dispose();
    super.dispose();
  }

  void _openAddEventModal() {
    final titleController = TextEditingController();
    DateTime? start;
    DateTime? end;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true, // pour que le clavier pousse bien la sheet
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 16,
            right: 16,
            top: 16,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: titleController,
                decoration: InputDecoration(labelText: "Titre"),
              ),
              SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton(
                      child: Text(start == null
                          ? "Choisir début"
                          : start.toString()),
                      onPressed: () async {
                        final date = await showDatePicker(
                          context: context,
                          initialDate: DateTime.now(),
                          firstDate: DateTime(2000),
                          lastDate: DateTime(2100),
                        );
                        if (date != null) {
                          final time = await showTimePicker(
                            context: context,
                            initialTime: TimeOfDay.now(),
                          );
                          if (time != null) {
                            setState(() {
                              start = DateTime(
                                date.year,
                                date.month,
                                date.day,
                                time.hour,
                                time.minute,
                              );
                            });
                          }
                        }
                      },
                    ),
                  ),
                  SizedBox(width: 8),
                  Expanded(
                    child: ElevatedButton(
                      child:
                      Text(end == null ? "Choisir fin" : end.toString()),
                      onPressed: () async {
                        final date = await showDatePicker(
                          context: context,
                          initialDate: start ?? DateTime.now(),
                          firstDate: DateTime(2000),
                          lastDate: DateTime(2100),
                        );
                        if (date != null) {
                          final time = await showTimePicker(
                            context: context,
                            initialTime: TimeOfDay.now(),
                          );
                          if (time != null) {
                            setState(() {
                              end = DateTime(
                                date.year,
                                date.month,
                                date.day,
                                time.hour,
                                time.minute,
                              );
                            });
                          }
                        }
                      },
                    ),
                  ),
                ],
              ),
              SizedBox(height: 20),
              ElevatedButton(
                child: Text("Ajouter l'événement"),
                onPressed: () {
                  if (titleController.text.isNotEmpty && start != null && end != null) {
                    _eventController.add(CalendarEventData(
                      title: titleController.text,
                      date: start!,
                      startTime: start!,
                      endTime: end!,
                    ));
                    Navigator.pop(context);
                  }
                },
              ),
              SizedBox(height: 20),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    // This method is rerun every time setState is called, for instance as done
    // by the _incrementCounter method above.
    //
    // The Flutter framework has been optimized to make rerunning build methods
    // fast, so that you can just rebuild anything that needs updating rather
    // than having to individually change instances of widgets.
    return Scaffold(
      appBar: AppBar(
        // TRY THIS: Try changing the color here to a specific color (to
        // Colors.amber, perhaps?) and trigger a hot reload to see the AppBar
        // change color while the other colors stay the same.
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        // Here we take the value from the MyHomePage object that was created by
        // the App.build method, and use it to set our appbar title.
        title: Text(widget.title),
      ),
      body: Center(
        child: DayView(
          controller: _eventController,
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _openAddEventModal,
        tooltip: 'Increment',
        child: const Icon(Icons.add),
      ), // This trailing comma makes auto-formatting nicer for build methods.
    );
  }
}