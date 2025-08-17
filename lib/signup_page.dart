import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;

class SignupPage extends StatefulWidget {
  final VoidCallback onSignupSuccess;

  const SignupPage({super.key, required this.onSignupSuccess});

  @override
  State<SignupPage> createState() => _SignupPageState();
}

class _SignupPageState extends State<SignupPage> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();

  bool _isLoading = false;

  Future<void> _createAccount() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse("https://magical.stefbart.ch/api.php?route=users"), // 🔥 adapte l’URL
        headers: {"Content-Type": "application/json"},
        body: jsonEncode({
          "name": _nameController.text,
          "email": _emailController.text,
          "password": _passwordController.text,
        }),
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> result = jsonDecode(response.body);

        if (result["status"] == "success") {
          final data = result["data"] as Map<String, dynamic>;
          final token = data["token"] as String;
          final userId = data["id"] as int;

          // Sauvegarde dans SharedPreferences
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString("authToken", token);
          await prefs.setInt("userId", userId);

          // Redirige vers HomePage
          widget.onSignupSuccess();
        } else {
          _showError("Erreur: ${result["message"] ?? "Inconnue"}");
        }
      } else {
        _showError("Erreur serveur: ${response.statusCode}");
      }
    } catch (e) {
      _showError("Erreur réseau: $e");
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Créer un compte")),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            TextField(controller: _nameController, decoration: const InputDecoration(labelText: "Nom")),
            TextField(controller: _emailController, decoration: const InputDecoration(labelText: "Email")),
            TextField(controller: _passwordController, decoration: const InputDecoration(labelText: "Mot de passe"), obscureText: true),
            const SizedBox(height: 20),
            _isLoading
                ? const CircularProgressIndicator()
                : ElevatedButton(
              onPressed: _createAccount,
              child: const Text("Créer mon compte"),
            ),
          ],
        ),
      ),
    );
  }
}
